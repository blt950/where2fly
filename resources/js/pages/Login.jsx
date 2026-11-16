import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

import FieldError from '../components/form/FieldError';
import Turnstile from '../components/form/Turnstile';
import PageTitle from '../components/ui/PageTitle';

const Login = ({ turnstileKey, old }) => {
    const form = useForm({ username: old.username ?? '', password: '', remember: Boolean(old.remember), 'cf-turnstile-response': '' });
    const { data, setData, errors, processing } = form;
    const [attempt, setAttempt] = useState(0);

    // A failed attempt stays here: clear the password, and get a fresh challenge for the next one
    const submit = (event) => {
        event.preventDefault();
        form.post(route('user.login'), {
            onFinish: () => {
                form.reset('password');
                setAttempt((count) => count + 1);
            },
        });
    };

    return (
        <>
            <Head title="Login" />
            <PageTitle title="Login" />

            <div className="container">
                <div className="mb-4">
                    <Link className="font-size-1rem" href={route('register')}>
                        <i className="fa-sharp fa-user-plus"></i> Don&apos;t have an account? Register here
                    </Link>
                </div>

                <form onSubmit={submit}>
                    <div className="mb-3">
                        <label htmlFor="username">Username <small className="form-text text-white-50">or e-mail</small></label>
                        <input name="username" type="text" className="form-control" id="username" autoComplete="username"
                            value={data.username} onChange={(e) => setData('username', e.target.value)} />
                        <FieldError message={errors.username} />
                    </div>

                    <div className="mb-3">
                        <label htmlFor="password">Password</label>
                        <input name="password" type="password" className="form-control" id="password" autoComplete="current-password"
                            value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        <Link href={route('account.recovery')}>Forgot your password?</Link>
                        <FieldError message={errors.password} />
                    </div>

                    <Turnstile siteKey={turnstileKey} action="login" resetKey={attempt}
                        onToken={(token) => setData('cf-turnstile-response', token)} />
                    <FieldError message={errors['cf-turnstile-response']} />

                    <div className="mt-2 mb-3">
                        <input name="remember" type="checkbox" className="form-check-input" id="remember"
                            checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                        <label htmlFor="remember">&nbsp;Remember me</label>
                    </div>

                    <button type="submit" className="btn btn-primary" disabled={processing}>LOGIN</button>
                </form>
            </div>
        </>
    );
};

// A form to fill in: the drawer opens fully, and the map stays as the last page left it
Login.keepsMap = true;
Login.drawer = 'full';

export default Login;
