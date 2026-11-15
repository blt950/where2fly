import { Head, Link, useForm } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import PageTitle from '../components/ui/PageTitle';

const DEFAULT_COLOR = '#8596e7';

// Creating and editing a list: one form. `old` refills it after a failed save.
const ListForm = ({ list, simulators, canMakePublic, old }) => {
    const form = useForm({
        color: old.color ?? list?.color ?? DEFAULT_COLOR,
        name: old.name ?? list?.name ?? '',
        simulator: String(old.simulator ?? list?.simulator ?? simulators[0]?.id ?? ''),
        airports: old.airports ?? list?.airports ?? '',
        public: Boolean(old.public ?? list?.public ?? false),
    });
    const { data, setData, errors, processing } = form;

    const submit = (event) => {
        event.preventDefault();
        form.post(list ? route('list.update', list.id) : route('list.store'));
    };

    const confirmDelete = (event) => {
        if (!window.confirm('Are you sure you want to delete this list?')) {
            event.preventDefault();
        }
    };

    return (
        <>
            <Head title={list ? 'Edit list' : 'Your lists'} />
            <PageTitle title="Your lists" />

            <div className="container">
                <form onSubmit={submit}>
                    <div className="mb-3">
                        <label htmlFor="color" className="form-label">Color</label>
                        <input type="color" id="color" className="d-block" value={data.color} onChange={(e) => setData('color', e.target.value)} />
                        <FieldError message={errors.color} />
                    </div>
                    <div className="mb-3">
                        <label htmlFor="name" className="form-label">Name</label>
                        <input type="text" className="form-control" id="name" maxLength="32" required
                            value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <FieldError message={errors.name} />
                    </div>
                    <div className="mb-3">
                        <label htmlFor="simulator" className="form-label">Simulator</label>
                        <select className="form-control form-select" id="simulator" required
                            value={data.simulator} onChange={(e) => setData('simulator', e.target.value)}>
                            {simulators.map((simulator) => <option key={simulator.id} value={simulator.id}>{simulator.name}</option>)}
                        </select>
                        <FieldError message={errors.simulator} />
                    </div>
                    <div className="mb-3">
                        <label htmlFor="airports" className="form-label">Airports</label>
                        <small className="form-text text-white-50"> Separate airports by new line</small>
                        <textarea className="form-control h-100" id="airports" rows="8" required
                            value={data.airports} onChange={(e) => setData('airports', e.target.value)}></textarea>
                        <FieldError message={errors.airports} />
                    </div>

                    {canMakePublic && (
                        <div className="mb-3">
                            <input className="form-check-input" type="checkbox" id="public"
                                checked={data.public} onChange={(e) => setData('public', e.target.checked)} />
                            <label className="form-check-label" htmlFor="public">&nbsp;Public</label>
                            <FieldError message={errors.public} />
                        </div>
                    )}

                    {list ? (
                        <div className="d-flex justify-content-between">
                            <Link href={route('list.delete', list.id)} className="btn btn-danger" onClick={confirmDelete}>
                                <i className="fa-sharp fa-trash"></i> Delete list
                            </Link>
                            <button type="submit" className="btn btn-primary" disabled={processing}>
                                <i className="fa-sharp fa-save"></i> UPDATE
                            </button>
                        </div>
                    ) : (
                        <button type="submit" className="btn btn-primary" disabled={processing}>Create list</button>
                    )}
                </form>
            </div>
        </>
    );
};

// A list to read or a form to fill in: the drawer opens fully on the compact layout
ListForm.drawer = 'full';

export default ListForm;
