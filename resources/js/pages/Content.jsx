import { Head } from '@inertiajs/react';

import PageTitle from '../components/ui/PageTitle';

// A text page (changelog, privacy policy, API docs). The body is our own Blade partial, rendered
// on the server, so it is trusted HTML; keeping it in Blade keeps it editable as it always was.
const Content = ({ title, tab, html }) => (
    <>
        <Head title={tab} />
        <PageTitle title={title} />
        <div dangerouslySetInnerHTML={{ __html: html }} />
    </>
);

export default Content;
