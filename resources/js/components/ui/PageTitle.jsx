// The title block of layouts/title.blade.php; `children` holds the page's actions below it.
const PageTitle = ({ title, subtitle, children }) => (
    <div className="title">
        <h1>{title}</h1>
        {subtitle && <h2 className="mb-0">{subtitle}</h2>}
        {children}
    </div>
);

export default PageTitle;
