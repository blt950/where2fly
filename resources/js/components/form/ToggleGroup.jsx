import { Fragment } from 'react';

// A row of radio buttons styled as a button group (Exclude / Neutral / Include and the like).
// `value` is the checked option; undefined checks none, as the Blade form did for unset old input.
const ToggleGroup = ({ name, idPrefix = name, options, value, labelClass = 'btn-filter-width' }) => (
    <div className="btn-group" role="group" aria-label="Basic radio toggle button group">
        {options.map((option) => (
            // Bare siblings: .btn-group styles its buttons through direct-child selectors
            <Fragment key={option.value}>
                <input type="radio" className={`btn-check ${option.tone}`} name={name} value={option.value}
                    id={`${idPrefix}_${option.id}`} defaultChecked={value !== undefined && String(value) === String(option.value)} />
                <label className={`btn btn-sm btn-dark ${labelClass}`} htmlFor={`${idPrefix}_${option.id}`}>{option.label}</label>
            </Fragment>
        ))}
    </div>
);

const hidden = (text) => <span className="visually-hidden">{text}</span>;

// Exclude / Neutral / Include, with the tones the scores and destination filters use
export const triState = (tones = ['red', 'light', 'green']) => [
    { value: '-1', id: 'exclude', tone: tones[0], label: <><i className="fa-sharp fa-xmark"></i>{hidden('Exclude')}</> },
    { value: '0', id: 'neutral', tone: tones[1], label: <><i className="fa-sharp fa-slash-forward"></i>{hidden('Neutral')}</> },
    { value: '1', id: 'include', tone: tones[2], label: <><i className="fa-sharp fa-check"></i>{hidden('Include')}</> },
];

export default ToggleGroup;
