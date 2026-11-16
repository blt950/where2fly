import { Head, Link } from '@inertiajs/react';

import PageTitle from '../components/ui/PageTitle';

const Admin = ({ stats, contributions }) => (
    <>
        <Head title="Admin" />

        <PageTitle title="Admin" />

        <div className="container">
            <h2>Stats</h2>
            <ul>
                <li>Users: {stats.users}</li>
                <li>Lists: {stats.lists}</li>
                <li>Sceneries: {stats.sceneries}</li>
            </ul>

            <h2>Scenery Contributions</h2>
            {contributions.length === 0 ? (
                <p>No scenery contributions</p>
            ) : (
                <ul>
                    {contributions.map((scenery) => (
                        <li key={scenery.id}>
                            <span className="badge bg-dark">{scenery.simulator}</span>{' '}
                            <Link href={route('scenery.edit', scenery.id)}>{scenery.icao}</Link>{' '}
                            <span className="text-white-50">by {scenery.suggestedBy}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    </>
);

// Nothing to show on the map; leave it as it was
Admin.keepsMap = true;
Admin.drawer = 'full';

export default Admin;
