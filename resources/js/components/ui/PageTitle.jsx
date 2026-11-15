// The title block of layouts/title.blade.php. The search "edit filters" form joins it in M3.
const PageTitle = ({ title, subtitle }) => (
    <div className="title">
        <h1>{title}</h1>
        {subtitle && <h2 className="mb-0">{subtitle}</h2>}
    </div>
);

export default PageTitle;
