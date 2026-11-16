import { useRef, useState } from 'react';

import { postForm, reportError } from '../utils/http';
import FieldError from './FieldError';

const codesOf = (text) => text.split(/\r\n|\r|\n/).map((line) => line.trim().toUpperCase()).filter(Boolean);

const plural = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;

// The import tool under the list's Airports field: picking an MSFS 2024 Content.xml reads it on
// the server and adds its airports to the field. `airports` is the field's text, `onChange` replaces it.
const MsfsImport = ({ airports, onChange }) => {
    const fileInput = useRef(null);
    // The picked file, kept to read it again when the Asobo / Microsoft box changes
    const file = useRef(null);
    // What the import put in the field, so reading again (or Undo) takes exactly that back out
    const imported = useRef([]);
    const [helpOpen, setHelpOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [result, setResult] = useState(null);

    const listed = new Set(codesOf(airports));
    const withoutImported = () => codesOf(airports).filter((code) => !imported.current.includes(code));

    const read = async (includeNative) => {
        const body = new FormData();
        body.append('content_xml', file.current);
        body.append('include_native', includeNative ? '1' : '0');

        setLoading(true);
        setError(null);

        try {
            const response = await postForm(route('list.import.msfs'), body);
            const json = await response.json().catch(() => null);

            if (!response.ok) {
                setError(response.status === 429
                    ? 'Too many imports in a short time. Wait a minute and try again.'
                    : json?.message ?? 'The file could not be read. Try again.');

                return;
            }

            const kept = withoutImported();
            imported.current = (json.suggestion ? json.suggestion.split('\n') : []).filter((code) => !kept.includes(code));
            onChange([...kept, ...imported.current].join('\n'));
            setResult(json);
        } catch (e) {
            reportError(e);
            setError('The file could not be uploaded. Check your connection and try again.');
        } finally {
            setLoading(false);
        }
    };

    const pick = (event) => {
        const [picked] = event.target.files;
        // Cleared so picking the same file again (after restarting the sim) still fires a change
        event.target.value = '';

        if (picked) {
            file.current = picked;
            read(result?.include_native ?? false);
        }
    };

    const undo = () => {
        onChange(withoutImported().join('\n'));
        imported.current = [];
        file.current = null;
        setResult(null);
        setError(null);
    };

    const toggleGuess = (icao, checked) => {
        const codes = codesOf(airports);
        onChange((checked ? [...codes, icao] : codes.filter((code) => code !== icao)).join('\n'));
    };

    const found = new Set(result?.airports.map((airport) => airport.icao));
    const nativeCount = result?.include_native
        ? result.airports.filter((airport) => airport.category === 'native').length
        : result?.stats.native_only_available ?? 0;

    // One row per guessed airport: several packages can point at the same one
    const guesses = Object.entries((result?.needs_review ?? [])
        .filter((item) => item.suggested_icao && !found.has(item.suggested_icao))
        .reduce((byIcao, item) => ({ ...byIcao, [item.suggested_icao]: [...(byIcao[item.suggested_icao] ?? []), item.package] }), {}));
    // An Asobo / Microsoft package only matters while those airports are being added
    const unmatched = result?.errors.filter((item) => result.include_native || item.category !== 'native') ?? [];
    const unresolved = result?.needs_review.filter((item) => !item.suggested_icao) ?? [];

    return (
        <div className="list-import">
            <div className="list-import-actions">
                <button type="button" className="btn btn-outline-light btn-sm" disabled={loading} onClick={() => fileInput.current.click()}>
                    <i className={`fa-sharp ${loading ? 'fa-spinner fa-spin' : 'fa-file-import'}`}></i>{' '}
                    {loading ? 'Reading…' : result ? 'Import another file' : 'Import from MSFS 2024'}
                </button>
                <button type="button" className="btn btn-link btn-sm" aria-expanded={helpOpen} aria-controls="msfs-import-help"
                    onClick={() => setHelpOpen(!helpOpen)}>
                    Where do I find the file?
                </button>
                <input type="file" className="visually-hidden" tabIndex="-1" aria-label="Content.xml"
                    accept=".xml,text/xml,application/xml" ref={fileInput} onChange={pick} />
            </div>

            {helpOpen && (
                <div className="list-import-help" id="msfs-import-help">
                    <p>MSFS 2024 keeps a <code>Content.xml</code> of your enabled packages. Pick it and the airports of your add-on scenery are added to the list.</p>
                    <dl>
                        <dt>Microsoft Store / Game Pass</dt>
                        <dd><code>%LOCALAPPDATA%\Packages\Microsoft.Limitless_8wekyb3d8bbwe\LocalCache</code></dd>
                        <dt>Steam</dt>
                        <dd><code>%APPDATA%\Microsoft Flight Simulator 2024</code></dd>
                    </dl>
                    <ul>
                        <li>The file is written when the simulator starts: start MSFS once after changing add-ons.</li>
                        <li>If you find more than one, use the most recently modified.</li>
                        <li>The file is only read to build this list. It is not stored.</li>
                        <li>MSFS 2020 files are not supported.</li>
                    </ul>
                </div>
            )}

            <FieldError message={error} className="validation-error mt-2" />

            {result && (
                <div className="list-import-result">
                    <div className="list-import-summary">
                        <b role="status">
                            {result.count > 0
                                ? <><i className="fa-sharp fa-check"></i> {plural(result.count, 'airport')} added from your file</>
                                : 'No add-on airports found in your file'}
                        </b>
                        <button type="button" className="btn btn-link btn-sm" onClick={undo}>Undo</button>
                    </div>

                    {nativeCount > 0 && (
                        <div className="form-check">
                            <input className="form-check-input" type="checkbox" id="include_native" disabled={loading}
                                checked={result.include_native} onChange={(e) => read(e.target.checked)} />
                            <label className="form-check-label" htmlFor="include_native">
                                Also add {plural(nativeCount, 'airport')} from Asobo / Microsoft packages
                            </label>
                        </div>
                    )}

                    {guesses.length > 0 && (
                        <fieldset>
                            <legend>Are these airports?</legend>
                            <p className="list-import-hint">Guessed from the package name. Tick the ones that are right.</p>
                            {guesses.map(([icao, packages]) => (
                                <div className="form-check" key={icao}>
                                    <input className="form-check-input" type="checkbox" id={`guess-${icao}`}
                                        checked={listed.has(icao)} onChange={(e) => toggleGuess(icao, e.target.checked)} />
                                    <label className="form-check-label" htmlFor={`guess-${icao}`}>
                                        <b>{icao}</b> <span className="list-import-package">{packages.join(', ')}</span>
                                    </label>
                                </div>
                            ))}
                        </fieldset>
                    )}

                    {unmatched.length > 0 && (
                        <details>
                            <summary>{plural(unmatched.length, 'airport')} we could not add</summary>
                            <p className="list-import-hint">These codes are not in our airport database.</p>
                            <ul>
                                {unmatched.map((item) => (
                                    <li key={item.package}><b>{item.icao}</b> <span className="list-import-package">{item.package}</span></li>
                                ))}
                            </ul>
                        </details>
                    )}

                    {unresolved.length > 0 && (
                        <details>
                            <summary>{plural(unresolved.length, 'other package')} without an airport code</summary>
                            <p className="list-import-hint">Mostly aircraft, liveries and tools. If one is an airport, type its code in the list.</p>
                            <ul>
                                {unresolved.map((item) => <li key={item.package} className="list-import-package">{item.package}</li>)}
                            </ul>
                        </details>
                    )}

                    <p className="list-import-hint mb-0">Something missing? Start MSFS once after changing add-ons, then import the newest file.</p>
                </div>
            )}
        </div>
    );
};

export default MsfsImport;
