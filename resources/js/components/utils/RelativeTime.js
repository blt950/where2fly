const UNITS = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
    ['second', 1],
];

const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

// "2 days ago" / "last month" style relative time, replaces moment's fromNow()
export default function fromNow(date) {
    const seconds = (new Date(date).getTime() - Date.now()) / 1000;

    for (const [unit, secondsInUnit] of UNITS) {
        if (Math.abs(seconds) >= secondsInUnit || unit === 'second') {
            return rtf.format(Math.round(seconds / secondsInUnit), unit);
        }
    }
}
