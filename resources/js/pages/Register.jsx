import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import Turnstile from '../components/form/Turnstile';
import PageTitle from '../components/ui/PageTitle';

const PERKS = [
    'Create personal airports lists visible on the map',
    'Use your own lists as search whitelists',
    'Contribute with scenery links for airports',
];

const Register = ({ turnstileKey, old }) => {
    const form = useForm({
        username: old.username ?? '',
        email: old.email ?? '',
        password: '',
        password_confirmation: '',
        privacy_policy: false,
        'cf-turnstile-response': '',
    });
    const { data, setData, errors, processing } = form;
    const [attempt, setAttempt] = useState(0);

    const submit = (event) => {
        event.preventDefault();
        form.post(route('user.register'), {
            onError: () => {
                form.reset('password', 'password_confirmation');
                setAttempt((count) => count + 1);
            },
        });
    };

    const field = (name, label, type, hint, autoComplete) => (
        <div className="mb-3">
            <label htmlFor={name}>{label}</label>
            {hint && <small className="form-text text-white-50"> {hint}</small>}
            <input name={name} type={type} className="form-control" id={name} required autoComplete={autoComplete}
                value={data[name]} onChange={(e) => setData(name, e.target.value)} />
            <FieldError message={errors[name]} />
        </div>
    );

    return (
        <>
            <Head title="Register" />
            <PageTitle title="Register" />

            <div className="container">
                <div className="pb-4 mb-4 border-bottom">
                    <h2>Account Perks</h2>
                    {PERKS.map((perk) => (
                        <div key={perk}>
                            <i className="fa-sharp fa-check-circle text-success"></i> <span>{perk}</span>
                        </div>
                    ))}
                </div>

                <h2 className="mb-3">Registration</h2>
                <form onSubmit={submit}>
                    {field('username', 'Username', 'text', 'Used to login and it\'s visible to other users.', 'username')}
                    {field('email', 'Email address', 'email', 'Only used to verify or recover your account, no marketing.', 'email')}
                    {field('password', 'Password', 'password', null, 'new-password')}
                    {field('password_confirmation', 'Confirm Password', 'password', null, 'new-password')}

                    <div className="form-check mb-3">
                        <input name="privacy_policy" type="checkbox" className="form-check-input" id="acceptTerms" required
                            checked={data.privacy_policy} onChange={(e) => setData('privacy_policy', e.target.checked)} />
                        <label className="form-check-label" htmlFor="acceptTerms">
                            I accept the <a href={route('privacy')} target="_blank" rel="noreferrer">privacy policy</a>
                        </label>
                        <FieldError message={errors.privacy_policy} />
                    </div>

                    <Turnstile siteKey={turnstileKey} action="register" resetKey={attempt}
                        onToken={(token) => setData('cf-turnstile-response', token)} />
                    <FieldError message={errors['cf-turnstile-response']} />

                    <button type="submit" className="btn btn-primary mt-2" disabled={processing}>REGISTER</button>
                </form>
            </div>
        </>
    );
};

Register.keepsMap = true;
Register.drawer = 'full';

export default Register;
