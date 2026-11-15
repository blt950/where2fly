import { useMemo, useState } from 'react';

const compare = (a, b) => {
    if (typeof a === 'number' && typeof b === 'number') {
        return a - b;
    }

    return String(a).localeCompare(String(b));
};

// Column sorting in React state; sortable-tablesort reorders DOM nodes, which React then fights.
// `columns` maps a column key to the value a row sorts by; the first click sorts ascending, as
// sortable-tablesort does on a table with the `asc` class.
export const useSortableRows = (rows, columns, initial = { key: null, ascending: true }) => {
    const [sort, setSort] = useState(initial);

    const sorted = useMemo(() => {
        const value = columns[sort.key];

        if (!value) {
            return rows;
        }

        const direction = sort.ascending ? 1 : -1;

        return [...rows].sort((a, b) => direction * compare(value(a), value(b)));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [rows, sort]);

    const toggle = (key) => setSort((current) => ({
        key,
        ascending: current.key === key ? !current.ascending : true,
    }));

    // Spread onto a <th>: sortable.scss draws the arrow from aria-sort.
    const headerProps = (key) => ({
        onClick: () => toggle(key),
        'aria-sort': sort.key === key ? (sort.ascending ? 'ascending' : 'descending') : undefined,
    });

    return { rows: sorted, sort, headerProps };
};
