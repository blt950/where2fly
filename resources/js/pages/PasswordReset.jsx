import { Head, useForm } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import PageTitle from '../components/ui/PageTitle';

// Step 2, from the emailed link: `token` from its path, `email` from its query
const PasswordReset = ({ token, email }) => {
    const form = useForm({ token, email: email ?? '', password: '', password_confirmation: '' });
    const { data, setData, errors, processing } = form;

    const submit = (event) => {
        event.preventDefault();
        form.post(route('password.update'), { onError: () => form.reset('password', 'password_confirmation') });
    };

    const field = (name, label, type, autoComplete) => (
        <div className="mb-3">
            <label htmlFor={name}>{label}</label>
            <input name={name} type={type} className="form-control" id={name} autoComplete={autoComplete}
                value={data[name]} onChange={(e) => setData(name, e.target.value)} />
            <FieldError message={errors[name]} />
        </div>
    );

    return (
        <>
            <Head title="Reset Account" />
            <PageTitle title="Reset Account" subtitle="Step 2 of 2" />

            <div className="container">
                <form onSubmit={submit}>
                    {field('email', 'Email address', 'email', 'email')}
                    {field('password', 'New password', 'password', 'new-password')}
                    {field('password_confirmation', 'Confirm new password', 'password', 'new-password')}
                    <FieldError message={errors.token} />

                    <button type="submit" className="btn btn-primary mt-2" disabled={processing}>RESET PASSWORD</button>
                </form>
            </div>
        </>
    );
};

PasswordReset.keepsMap = true;
PasswordReset.drawer = 'full';

export default PasswordReset;
