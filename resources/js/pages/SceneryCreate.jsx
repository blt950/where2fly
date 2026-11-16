import { Head, router, useForm } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import ExistingSceneries from '../components/ui/ExistingSceneries';
import PageTitle from '../components/ui/PageTitle';

// Contributing a scenery link. `existing` is what the airport in `?icao=` already has (null
// without a known airport).
const EMPTY = { icao: '', developer: '', link: '', payware: '', simulators: [] };

const SceneryCreate = ({ icao, simulators, existing }) => {
    const form = useForm({ ...EMPTY, icao });
    const { data, setData, errors, processing } = form;

    const submit = (event) => {
        event.preventDefault();
        // The redirect lands on this same page, which keeps its state: start a blank form for the next one
        form.post(route('scenery.store'), { onSuccess: () => form.setData(EMPTY) });
    };

    // Leaving the field looks the airport up; only `existing` is asked for, so the form stays
    const lookUp = () => {
        const next = data.icao.trim().toUpperCase();

        if (next && next !== icao) {
            router.get(route('scenery.create'), { icao: next }, { only: ['icao', 'existing'], preserveState: true, preserveScroll: true, replace: true });
        }
    };

    const toggleSimulator = (id, checked) => setData('simulators', checked
        ? [...data.simulators, id]
        : data.simulators.filter((selected) => selected !== id));

    return (
        <>
            <Head title="Scenery Contribution" />

            <PageTitle title="Scenery Contribution" />

            <div className="container">
                <p>Contribute with scenery links for airports. After submission we will review it and add it to the database if it meets our criteria.</p>

                <form onSubmit={submit}>
                    <div className="mb-3">
                        <label htmlFor="icao" className="form-label">ICAO</label>
                        <input type="text" className="form-control" id="icao" maxLength="10" required
                            value={data.icao} onChange={(e) => setData('icao', e.target.value.toUpperCase())} onBlur={lookUp} />
                        <FieldError message={errors.icao} />
                    </div>

                    {existing?.length > 0 && <ExistingSceneries sceneries={existing} />}

                    <div className="mb-3">
                        <label htmlFor="developer" className="form-label">Developer</label>
                        <input type="text" className="form-control" id="developer" maxLength="256" required
                            value={data.developer} onChange={(e) => setData('developer', e.target.value)} />
                        <FieldError message={errors.developer} />
                    </div>

                    <div className="mb-3">
                        <label htmlFor="link" className="form-label">Link</label>
                        <small className="form-text text-white-50"> Preferably to an English official store or platform</small>
                        <input type="url" className="form-control" id="link" maxLength="256" required
                            value={data.link} onChange={(e) => setData('link', e.target.value)} />
                        <FieldError message={errors.link} />
                    </div>

                    <div className="mb-3">
                        <label className="form-label" htmlFor="payware">Payware</label>
                        <select className="form-control form-select" id="payware" required
                            value={data.payware} onChange={(e) => setData('payware', e.target.value)}>
                            <option value="" disabled>Select</option>
                            <option value="1">Payware</option>
                            <option value="0">Freeware</option>
                        </select>
                        <FieldError message={errors.payware} />
                    </div>

                    <div className="mb-3">
                        <span className="form-label d-inline-block">Simulator</span>
                        <small className="form-text text-white-50"> Choose only simulator(s) the linked scenery officially supports.</small>
                        {simulators.map((simulator) => (
                            <div className="form-check" key={simulator.id}>
                                <input className="form-check-input" type="checkbox" id={`simulator_${simulator.id}`}
                                    checked={data.simulators.includes(simulator.id)}
                                    onChange={(e) => toggleSimulator(simulator.id, e.target.checked)} />
                                <label className="form-check-label" htmlFor={`simulator_${simulator.id}`}>{simulator.name}</label>
                            </div>
                        ))}
                        <FieldError message={errors.simulators} />
                    </div>

                    <button type="submit" className="btn btn-primary mt-3" disabled={processing}>Submit Contribution</button>
                </form>
            </div>
        </>
    );
};

// A form to fill in: the drawer opens fully, and the map stays as the last page left it
SceneryCreate.keepsMap = true;
SceneryCreate.drawer = 'full';

export default SceneryCreate;
