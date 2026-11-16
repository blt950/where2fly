import { useEffect, useRef, useState } from 'react';
import * as noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';

// A two-handle noUiSlider writing into two hidden inputs, so the form submits it like any field.
// noUiSlider owns its element's DOM; React only renders the empty host.
const RangeSlider = ({ id, names, range, step, start, describe }) => {

    const sliderRef = useRef(null);
    const [values, setValues] = useState(start);

    useEffect(() => {
        const slider = noUiSlider.create(sliderRef.current, {
            start,
            step,
            connect: true,
            behaviour: 'drag',
            range: { min: [range[0]], max: [range[1]] },
            handleAttributes: [{ 'aria-label': 'lower' }, { 'aria-label': 'upper' }],
        });

        slider.on('update', (raw) => setValues(raw.map((value) => Math.round(value))));

        return () => slider.destroy();
        // Created once per mount, like the rest of the form
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <>
            <input type="hidden" id={names[0]} name={names[0]} value={values[0]} />
            <input type="hidden" id={names[1]} name={names[1]} value={values[1]} />
            <div id={id} ref={sliderRef} className="mt-1 mb-1"></div>
            <span id={`${id}-text`}>{describe(values)}</span>
        </>
    );
};

export default RangeSlider;
