import { Head, useForm } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import PageTitle from '../components/ui/PageTitle';

// Step 1 of a password reset: the link goes to the email address
const AccountRecovery = ({ old }) => {
    const form = useForm({ email: old.email ?? '' });
    const { data, setData, errors, processing } = form;

    const submit = (event) => {
        event.preventDefault();
        form.post(route('account.recovery'));
    };

    return (
        <>
            <Head title="Reset Account" />
            <PageTitle title="Reset Account" subtitle="Step 1 of 2" />

            <div className="container">
                <form onSubmit={submit}>
                    <div className="mb-3">
                        <label htmlFor="email">Email address</label>
                        <input name="email" type="email" className="form-control" id="email" autoComplete="email"
                            value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <FieldError message={errors.email} />

                        <button type="submit" className="btn btn-primary mt-2" disabled={processing}>RESET ACCOUNT</button>
                    </div>
                </form>
            </div>
        </>
    );
};

AccountRecovery.keepsMap = true;
AccountRecovery.drawer = 'full';

export default AccountRecovery;
