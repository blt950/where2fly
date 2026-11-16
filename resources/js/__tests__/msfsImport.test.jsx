// @vitest-environment jsdom
import { useState } from 'react';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import MsfsImport from '../components/form/MsfsImport';

const result = (overrides = {}) => ({
    suggestion: 'EGKK\nENGM',
    count: 2,
    include_native: false,
    airports: [
        { icao: 'EGKK', category: 'community', also_native: false, packages: ['communityfs20-gatwick'] },
        { icao: 'ENGM', category: 'community', also_native: false, packages: ['communityfs20-aerosoft-airport-engm-oslo'] },
    ],
    errors: [
        { package: 'communityfs24-blt950-airport-ensa-sandviken-helipad', icao: 'ENSA', reason: 'icao_not_in_database', category: 'community' },
        { package: 'fs24-asobo-airport-eg78-outskerries', icao: 'EG78', reason: 'icao_not_in_database', category: 'native' },
    ],
    needs_review: [
        { package: 'communityfs20-efhk-helsinki', suggested_icao: 'EFHK', reason: 'icao_guessed_from_name', category: 'community' },
        { package: 'communityfs20-fsltl-traffic-base', suggested_icao: null, reason: 'no_icao_found', category: 'community' },
    ],
    stats: { native_only_available: 173 },
    ...overrides,
});

const WITH_NATIVE = result({
    suggestion: 'EGKK\nENGM\nKJFK',
    count: 3,
    include_native: true,
    airports: [...result().airports, { icao: 'KJFK', category: 'native', also_native: false, packages: ['fs24-asobo-airport-kjfk-new-york-jfk'] }],
    stats: { native_only_available: 0 },
});

const respond = (status, body) => Promise.resolve({ ok: status < 400, status, json: () => Promise.resolve(body) });

const Harness = ({ initial = '' }) => {
    const [airports, setAirports] = useState(initial);

    return (
        <>
            <textarea aria-label="Airports" value={airports} onChange={(e) => setAirports(e.target.value)} />
            <MsfsImport airports={airports} onChange={setAirports} />
        </>
    );
};

const chooseFile = () => fireEvent.change(screen.getByLabelText('Content.xml'), {
    target: { files: [new File(['<Packages/>'], 'Content.xml', { type: 'text/xml' })] },
});
const list = () => screen.getByLabelText('Airports').value;

beforeEach(() => {
    vi.stubGlobal('route', (name) => name);
    vi.stubGlobal('fetch', vi.fn());
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
});

describe('MsfsImport', () => {
    it('reads the file as soon as it is picked and adds the suggestion to what the list already holds', async () => {
        fetch.mockReturnValue(respond(200, result()));
        render(<Harness initial={'ENBR\nENGM'} />);

        chooseFile();

        await waitFor(() => expect(list()).toBe('ENBR\nENGM\nEGKK'));
        const [url, options] = fetch.mock.calls[0];
        expect(url).toBe('list.import.msfs');
        expect(options.body.get('content_xml').name).toBe('Content.xml');
        expect(options.body.get('include_native')).toBe('0');

        expect(screen.getByRole('status').textContent).toContain('2 airports added from your file');
        expect(screen.getByLabelText('Also add 173 airports from Asobo / Microsoft packages').checked).toBe(false);
        // EG78 is an Asobo package, and those are not being added
        expect(screen.getByText('1 airport we could not add').parentElement.textContent).toContain('ENSA communityfs24-blt950');
        expect(screen.queryByText('EG78')).toBeNull();
        expect(screen.getByText('communityfs20-fsltl-traffic-base')).toBeTruthy();
        expect(screen.getByRole('button', { name: /Import another file/ })).toBeTruthy();
    });

    it('adds a guess only when it is ticked, and takes it out again', async () => {
        fetch.mockReturnValue(respond(200, result({
            needs_review: [
                { package: 'communityfs20-kui-zudc', suggested_icao: 'ZUDC', reason: 'icao_guessed_from_name', category: 'community' },
                { package: 'communityfs20-kui-zudcsat', suggested_icao: 'ZUDC', reason: 'icao_guessed_from_name', category: 'community' },
                { package: 'communityfs20-engm-extras', suggested_icao: 'ENGM', reason: 'icao_guessed_from_name', category: 'community' },
            ],
        })));
        render(<Harness />);
        chooseFile();

        // One row for the two packages, and none for an airport the file already gave
        const guess = await screen.findByLabelText(/ZUDC/);
        expect(guess.checked).toBe(false);
        expect(screen.getAllByRole('checkbox')).toHaveLength(2);
        expect(screen.getByText('communityfs20-kui-zudc, communityfs20-kui-zudcsat')).toBeTruthy();

        fireEvent.click(guess);
        expect(list()).toBe('EGKK\nENGM\nZUDC');
        fireEvent.click(screen.getByLabelText(/ZUDC/));
        expect(list()).toBe('EGKK\nENGM');
    });

    it('reads the file again when the Asobo / Microsoft box changes, replacing its own airports only', async () => {
        fetch.mockReturnValueOnce(respond(200, result())).mockReturnValueOnce(respond(200, WITH_NATIVE)).mockReturnValueOnce(respond(200, result()));
        render(<Harness initial="ENBR" />);
        chooseFile();

        fireEvent.click(await screen.findByLabelText(/Also add 173 airports/));
        await waitFor(() => expect(list()).toBe('ENBR\nEGKK\nENGM\nKJFK'));
        expect(fetch.mock.calls[1][1].body.get('include_native')).toBe('1');
        expect(fetch.mock.calls[1][1].body.get('content_xml').name).toBe('Content.xml');

        expect(screen.getByText('2 airports we could not add')).toBeTruthy();
        const box = screen.getByLabelText('Also add 1 airport from Asobo / Microsoft packages');
        expect(box.checked).toBe(true);
        fireEvent.click(box);
        await waitFor(() => expect(list()).toBe('ENBR\nEGKK\nENGM'));
    });

    it('undoes the import, leaving what was typed', async () => {
        fetch.mockReturnValue(respond(200, result()));
        render(<Harness initial="ENBR" />);
        chooseFile();
        await waitFor(() => expect(list()).toBe('ENBR\nEGKK\nENGM'));

        fireEvent.click(screen.getByRole('button', { name: 'Undo' }));

        expect(list()).toBe('ENBR');
        expect(screen.queryByRole('status')).toBeNull();
        expect(screen.getByRole('button', { name: /Import from MSFS 2024/ })).toBeTruthy();
    });

    it('shows why a file was refused and leaves the list alone', async () => {
        fetch.mockReturnValue(respond(422, { message: 'This is not an MSFS 2024 Content.xml.', reason: 'unexpected_root' }));
        render(<Harness initial="ENBR" />);
        chooseFile();

        expect(await screen.findByText(/This is not an MSFS 2024 Content.xml/)).toBeTruthy();
        expect(list()).toBe('ENBR');
    });

    it('keeps where to find the file behind a toggle', () => {
        render(<Harness />);
        expect(screen.queryByText(/Microsoft Store/)).toBeNull();

        fireEvent.click(screen.getByRole('button', { name: 'Where do I find the file?' }));

        expect(screen.getByText(/Microsoft Store/)).toBeTruthy();
        expect(screen.getByText(/It is not stored/)).toBeTruthy();
    });
});
