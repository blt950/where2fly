import { Head, Link } from '@inertiajs/react';

import PageTitle from '../components/ui/PageTitle';

const Lists = ({ lists }) => (
    <>
        <Head title="My Lists" />
        <PageTitle title="My Lists" />

        <div className="container">
            <p className="mb-5">Create lists of airports to whitelist during search and see on the map. Perfect way to only find destinations you&apos;re interested in.</p>

            {lists.map((list) => (
                <div key={list.id} className="d-flex justify-content-between font-size-1rem mt-4">
                    <div className="d-flex flex-column">
                        <div className={`d-flex list-title${list.hidden ? ' hidden' : ''}`}>
                            <div style={{ background: list.color }}></div>
                            <strong>{list.name}</strong>
                        </div>
                        <span className={`list-airports${list.hidden ? ' hidden' : ''}`}>
                            <i className="fa-sharp fa-joystick"></i>&nbsp;{list.simulator}
                        </span>
                        <span className={`list-airports${list.hidden ? ' hidden' : ''}`}>
                            <i className="fa-sharp fa-tower-control"></i>&nbsp;{list.airportsCount} airports {list.hidden ? '(Hidden)' : ''}
                        </span>
                    </div>
                    <div>
                        <Link href={route('list.toggle', list.id)} className="btn btn-outline-light" preserveScroll>
                            {list.hidden
                                ? <><i className="fa-sharp fa-eye"></i> Show</>
                                : <><i className="fa-sharp fa-eye-slash"></i> Hide</>}
                        </Link>{' '}
                        <Link href={route('list.edit', list.id)} className="btn btn-outline-primary">
                            <i className="fa-sharp fa-pencil"></i> Edit
                        </Link>
                    </div>
                </div>
            ))}

            <Link href={route('list.create')} className="btn btn-success mt-5"><i className="fa-sharp fa-plus"></i> Create a new list</Link>
        </div>
    </>
);

// A list to read or a form to fill in: the drawer opens fully on the compact layout
Lists.drawer = 'full';

export default Lists;
