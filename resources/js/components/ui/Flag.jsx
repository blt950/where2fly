import { stopOnTouch } from '../utils/touch';

// A country's flag, its name in a tooltip. Focusable, so a tap opens the tooltip on a touch
// screen; inside a clickable row that tap is for the tooltip, not for the row.
const Flag = ({ country, name, className = 'flag' }) => (
    <img className={className} src={`/img/flags/${country.toLowerCase()}.svg`} height="16" tabIndex={0} onClick={stopOnTouch}
        data-bs-toggle="tooltip" data-bs-title={name} alt={`Flag of ${name}`} />
);

export default Flag;
