import { Head, Link } from '@inertiajs/react';

import PageTitle from '../components/ui/PageTitle';

const MESSAGES = {
    403: ['Not allowed', 'You don\'t have access to this page.'],
    404: ['Page not found', 'This page doesn\'t exist, or it has moved.'],
    500: ['Something went wrong', 'That one is on us. We\'ve been told about it, so please try again in a little while.'],
    503: ['Down for maintenance', 'Where2Fly is being updated. Please try again in a few minutes.'],
};

// What the server renders for an error (bootstrap/app.php), inside the shell like any other page
const ErrorPage = ({ status }) => {
    const [title, message] = MESSAGES[status] ?? MESSAGES[500];

    return (
        <>
            <Head title={title} />
            <PageTitle title={title} subtitle={`Error ${status}`} />

            <div className="container">
                <p>{message}</p>
                <Link href={route('front')} className="btn btn-outline-primary">
                    <i className="fa-sharp fa-search" aria-hidden="true"></i> Find a destination
                </Link>
            </div>
        </>
    );
};

// Nothing to show on the map, so it stays as the previous page left it
ErrorPage.keepsMap = true;

export default ErrorPage;
