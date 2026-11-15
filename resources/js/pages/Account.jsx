import { Head, router } from '@inertiajs/react';

import PageTitle from '../components/ui/PageTitle';

const deleteAccount = () => {
    if (window.confirm('Are you sure you want to delete your account?')) {
        router.post(route('user.delete'));
    }
};

const Account = ({ recoveryUrl }) => (
    <>
        <Head title="Account" />
        <PageTitle title="Account Settings" />

        <div className="container">
            <h2>Reset password</h2>
            <p>You may reset you password by performing an account reset. Your data will <b>not</b> be lost.</p>
            {/* The recovery flow is still Blade, so this is an ordinary page load */}
            <a href={recoveryUrl} className="btn btn-primary">Reset password</a>

            <h2 className="mt-4">Delete account</h2>
            <p>Deleting your account will remove all your data from the system. This action is irreversible.</p>
            <button type="button" className="btn btn-danger" onClick={deleteAccount}>Delete account</button>
        </div>
    </>
);

// Settings have nothing to show on the map; leave it as it was
Account.keepsMap = true;
Account.drawer = 'full';

export default Account;
