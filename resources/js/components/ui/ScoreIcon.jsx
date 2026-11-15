import { stopOnTouch } from '../utils/touch';

// The data comes from App\View\ScoreIcon::toArray(), so tooltip lines arrive escaped and safe
// to render as HTML.
const FACILITY_DOTS = ['fa-circle-quarter-stroke', 'fa-circle-half-stroke', 'fa-circle-three-quarters-stroke'];

const ScoreIcon = ({ score, highlighted = false }) => {
    const success = highlighted ? 'text-success ' : '';
    const dots = score.facilityDotCount;
    const dotIcon = dots > 3 ? 'fa-circle' : FACILITY_DOTS[dots - 1];

    return (
        <span
            className={`score-icon position-relative${score.uncertain ? ' score-uncertain' : ''}`}
            data-bs-html="true"
            data-bs-toggle="tooltip"
            data-bs-title={score.tooltipLines.join('<br>')}
            tabIndex={0}
            onClick={stopOnTouch}
        >
            <i className={`${success}fa-sharp ${score.icon}`}></i>
            {score.probabilityBadge && (
                <i className={`fa-sharp fa-dot position-absolute probability ${success}`} aria-hidden="true"></i>
            )}
            {dots > 0 && (
                <i className={`fa-sharp ${dotIcon} position-absolute atc ${success}`} aria-hidden="true"></i>
            )}
        </span>
    );
};

export default ScoreIcon;
