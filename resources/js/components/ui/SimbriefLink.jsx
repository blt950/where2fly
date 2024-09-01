const SimbriefLink = ({ direction, primaryIcao, secondaryIcao }) => {
    const simbriefUrl = `orig=${direction == false ? primaryIcao : secondaryIcao}&dest=${direction == false ? secondaryIcao : primaryIcao}`;

    return (
        <>
            {primaryIcao !== null ? (
                <a className="btn btn-outline-light btn-sm font-work-sans" href={`https://dispatch.simbrief.com/options/custom?${simbriefUrl}`} target="_blank">
                    <span>SimBrief</span> <i className="fas fa-up-right-from-square"></i>
                </a>
            ) : (
                <a className="btn btn-outline-light btn-sm font-work-sans" href={`https://dispatch.simbrief.com/options/custom?dest=${secondaryIcao}`} target="_blank">
                    <span>SimBrief</span> <i className="fas fa-up-right-from-square"></i>
                </a>
            )}
        </>
    );
};

export default SimbriefLink;