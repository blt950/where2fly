import { usePage } from '@inertiajs/react';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Session flash messages and the email-verification notice, as in layouts/app.blade.php.
const Alerts = () => {
    const { flash, auth } = usePage().props;
    const user = auth?.user;

    return (
        <>
            {flash?.error && (
                <div className="alert alert-danger" role="alert">
                    <i className="fa-sharp fa-lg fa-exclamation-circle"></i> {flash.error}
                </div>
            )}

            {flash?.warning && <div className="alert alert-warning" role="alert">{flash.warning}</div>}

            {flash?.success && <div className="alert alert-success" role="alert">{flash.success}</div>}

            {!flash?.success && user && !user.verified && (
                <div className="alert alert-warning" role="alert">
                    Your account is awaiting email verification.

                    {/* A plain form post: the verification flow is Blade and redirects back. */}
                    <form method="POST" action={route('verification.send')}>
                        <input type="hidden" name="_token" value={csrfToken()} />
                        <button className="btn btn-sm btn-primary text-secondary mt-2">
                            Resend verification email to {user.email}
                        </button>
                    </form>
                </div>
            )}
        </>
    );
};

export default Alerts;
