import { usePage } from '@inertiajs/react';

// Flash messages and the verification notice from AlertHelper (`alerts`);
// layouts/alerts.blade.php is its Blade twin
// `notices` are the shell's own, from this page's map (an airport it couldn't find)
const Alerts = ({ notices = [] }) => {
    const { alerts: { messages, verification }, csrfToken } = usePage().props;

    return (
        <>
            {[...messages, ...notices].map((message) => (
                <div key={message.key} className={`alert alert-${message.variant}`} role="alert">
                    {message.variant === 'danger' && <><i className="fa-sharp fa-lg fa-exclamation-circle"></i> </>}
                    {message.text}
                </div>
            ))}

            {verification && (
                <div className="alert alert-warning" role="alert">
                    Your account is awaiting email verification.

                    {/* A plain form post: the verification flow is Blade and redirects back */}
                    <form method="POST" action={verification.action}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button className="btn btn-sm btn-primary text-secondary mt-2">
                            Resend verification email to {verification.email}
                        </button>
                    </form>
                </div>
            )}
        </>
    );
};

export default Alerts;
