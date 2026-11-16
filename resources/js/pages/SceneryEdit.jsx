import { Head, Link, useForm } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import ExistingSceneries from '../components/ui/ExistingSceneries';
import PageTitle from '../components/ui/PageTitle';

// Reviewing a contributed scenery (admin). `existing` is everything its developer has at the
// airport, the one under review marked.
const SceneryEdit = ({ scenery, existing, suggestedBy }) => {
    const form = useForm({
        suggested_by_user_id: scenery.suggestedByUserId,
        icao: scenery.icao,
        developer: scenery.developer,
        link: scenery.link,
        // "Included in simulator" (-1) is neither option: the reviewer has to choose
        payware: [0, 1].includes(scenery.payware) ? String(scenery.payware) : '',
        published: scenery.published,
    });
    const { data, setData, errors, processing } = form;

    const submit = (event) => {
        event.preventDefault();
        form.post(route('scenery.update', scenery.id));
    };

    const confirmDelete = (event) => {
        if (!window.confirm('Are you sure you want to delete this scenery?')) {
            event.preventDefault();
        }
    };

    return (
        <>
            <Head title="Scenery Contribution" />

            <PageTitle title="Scenery Contribution" />

            <div className="container">
                <p>Submitted by {suggestedBy ?? 'N/A'}</p>

                <ExistingSceneries sceneries={existing} />

                <form onSubmit={submit}>
                    <h2 className="mb-0">Developer</h2>
                    <small className="form-text text-white-50">
                        Changing this will change the whole developer and connected sceneries ({existing.length}x)
                    </small>

                    <div className="mt-3 mb-3">
                        <label htmlFor="icao" className="form-label">ICAO</label>
                        <input type="text" className="form-control" id="icao" maxLength="10" required
                            value={data.icao} onChange={(e) => setData('icao', e.target.value.toUpperCase())} />
                        <FieldError message={errors.icao} />
                    </div>

                    <div className="mb-3">
                        <label htmlFor="developer" className="form-label">Developer</label>
                        <input type="text" className="form-control" id="developer" maxLength="256" required
                            value={data.developer} onChange={(e) => setData('developer', e.target.value)} />
                        <FieldError message={errors.developer} />
                    </div>

                    <h2>Scenery: {scenery.simulator}</h2>

                    <div className="mb-3">
                        <label htmlFor="link" className="form-label">Link</label>
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
                        <input className="form-check-input" type="checkbox" id="published"
                            checked={data.published} onChange={(e) => setData('published', e.target.checked)} />
                        <label className="form-check-label" htmlFor="published">&nbsp;<b>Published</b></label>
                        <FieldError message={errors.published} />
                    </div>

                    <div className="d-flex justify-content-between">
                        <Link href={route('scenery.delete', scenery.id)} className="btn btn-danger mt-3" onClick={confirmDelete}>
                            <i className="fa-sharp fa-trash"></i> Delete
                        </Link>
                        <button type="submit" className="btn btn-primary mt-3" disabled={processing}>Save</button>
                    </div>
                </form>
            </div>
        </>
    );
};

SceneryEdit.keepsMap = true;
SceneryEdit.drawer = 'full';

export default SceneryEdit;
