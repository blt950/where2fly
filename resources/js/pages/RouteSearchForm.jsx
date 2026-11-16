import { useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import SearchTabs from '../components/form/SearchTabs';
import { formToOld, formUrl } from '../components/form/formQuery';
import PageTitle from '../components/ui/PageTitle';

const IcaoField = ({ name, label, initial, error }) => {
    const [value, setValue] = useState(initial ?? '');

    return (
        <div className="col-xs-12 text-start">
            <label htmlFor={name}>{label} (ICAO)</label>
            <input type="text" className="form-control" id={name} name={name} required maxLength="4"
                value={value} onChange={(event) => setValue(event.target.value.toUpperCase())} />
            <FieldError message={error} />
        </div>
    );
};

const RouteSearchForm = ({ old }) => {

    const errors = usePage().props.errors ?? {};
    const formRef = useRef(null);
    const [submitting, setSubmitting] = useState(false);

    const submit = (event) => {
        event.preventDefault();

        if (!submitting) {
            const form = formRef.current;

            // Back from the results restores this entry's props, so record what was searched in them
            router.replace({ props: (props) => ({ ...props, old: formToOld(form) }), preserveState: true, preserveScroll: true });
            router.visit(formUrl(form), {
                onStart: () => setSubmitting(true),
                onFinish: () => setSubmitting(false),
            });
        }
    };

    return (
        <>
            <Head title="" />

            <PageTitle title="Search for your flight" subtitle="Find destinations based on your weather or coverage criteria" />

            <div className="container">
                <SearchTabs />

                <form id="form" ref={formRef} action={route('search.routes')} method="GET" onSubmit={submit}>
                    <div className="row g-3 justify-content-center">
                        <IcaoField name="departure" label="Departure" initial={old.departure} error={errors.departure} />
                        <IcaoField name="arrival" label="Arrival" initial={old.arrival} error={errors.arrival} />

                        <div className="col-xs-12 text-start">
                            <label htmlFor="sort">Sort by</label>
                            <select className="form-control" id="sort" name="sort" defaultValue={old.sort ?? 'flight'}>
                                <option value="flight">Callsigns</option>
                                <option value="timestamp">Last seen</option>
                            </select>
                            <FieldError message={errors.sort} />
                        </div>

                        <div className="col-sm-12 align-self-start">
                            <button type="submit" className="submitBtn btn btn-primary text-uppercase" disabled={submitting}>
                                Search{' '}
                                {submitting
                                    ? <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    : <i className="fa-sharp fa-search"></i>}
                            </button>
                        </div>
                    </div>

                    <FieldError message={errors.routeNotFound} className="validation-error mt-2" icon={false} />
                </form>
            </div>
        </>
    );
};

export default RouteSearchForm;
