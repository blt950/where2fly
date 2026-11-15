import { Head, Link, router } from '@inertiajs/react';

import BugReport from '../components/feedback/BugReport';
import WidePanel from '../components/shell/WidePanel';
import { useMapLayout } from '../components/context/MapLayoutContext';
import PageTitle from '../components/ui/PageTitle';

const Author = ({ author }) => (
    <div className="d-flex align-items-center flex-wrap">
        <img src={author.avatar} alt="" className="rounded-circle me-1" />
        <p className="card-text mb-0 me-2">{author.login}</p>
        <p className="card-text author mb-0">{author.created}</p>
    </div>
);

// `sidebar`: the card styles in cards.scss expect the list inside one, as on the old Blade page
const IdeaList = ({ issues, current, bugReports }) => (
    <div className="container sidebar">
        {bugReports && <BugReport />}

        <a href="https://github.com/blt950/where2fly/issues" className="btn btn-outline-success w-100 mb-3" target="_blank" rel="noopener">
            Add your new idea on Github <i className="fa-sharp fa-up-right-from-square"></i>
        </a>

        {issues.map((issue) => (
            <Link key={issue.number} className="sidebar card-link d-block" href={route('feedback.show', issue.number)} preserveScroll>
                <div className={`card mb-3${issue.isNew ? ' new' : ''}`}>
                    <div className={`card-body${current === issue.number ? ' active' : ''} d-flex justify-content-between align-items-center gap-1`}>
                        <div>
                            {issue.isNew && <span className="badge bg-primary text-black mb-2">New!</span>}
                            {issue.voted && <span className="badge bg-success mb-2"><i className="fa-sharp fa-check"></i> Voted</span>}
                            <div className="card-title">{issue.title}</div>
                            <Author author={issue.author} />
                        </div>
                        <div className="upvotes d-flex flex-column align-items-center flex-shrink-0">
                            <div className="fs-3 fw-bold">{issue.votes}</div>
                            Votes
                        </div>
                    </div>
                </div>
            </Link>
        ))}
    </div>
);

const VoteButton = ({ issue, canVote, loginUrl }) => {
    if (!canVote) {
        return <a href={loginUrl} className="btn btn-primary btn-sm me-2"><i className="fa-sharp fa-lock"></i> Log in to vote</a>;
    }

    if (issue.voted) {
        return (
            <Link href={route('feedback.vote.delete', { id: issue.number })} className="btn btn-outline-primary btn-sm me-2" preserveScroll>
                <i className="fa-sharp fa-xmark"></i> Remove vote
            </Link>
        );
    }

    return (
        <button type="button" className="btn btn-primary btn-sm me-2"
            onClick={() => router.post(route('feedback.vote'), { github_issue_number: issue.number }, { preserveScroll: true })}>
            <i className="fa-sharp fa-thumbs-up"></i> Add Vote
        </button>
    );
};

// Markdown rendered on the server from the GitHub issue, raw HTML stripped and unsafe links refused
const Markdown = ({ html }) => <div className="card-body" dangerouslySetInnerHTML={{ __html: html }} />;

const Idea = ({ issue, canVote, loginUrl }) => (
    <div className="feedback-container d-flex flex-column justify-content-center align-items-center gap-3">
        <div className="card">
            <div className="card-title">
                <div className="d-flex flex-row justify-content-between align-items-center flex-wrap">
                    <div className="d-flex flex-column">
                        <h3 className="mb-1">{issue.title}</h3>
                    </div>
                    <div className="w-200 align-items-center d-flex flex-row gap-1">
                        <div className="upvotes d-flex flex-row align-items-center justify-content-center gap-1">
                            <span className="fw-bold">{issue.votes}</span> {issue.votes === 1 ? 'Vote' : 'Votes'}
                        </div>
                        <VoteButton issue={issue} canVote={canVote} loginUrl={loginUrl} />
                    </div>
                </div>
            </div>
            <Markdown html={issue.html} />
            <div className="card-footer"><Author author={issue.author} /></div>
        </div>

        {issue.comments.map((comment, index) => (
            <div className="card" key={comment.id ?? index}>
                <Markdown html={comment.html} />
                <div className="card-footer"><Author author={comment.author} /></div>
            </div>
        ))}

        <a href={issue.url} className="btn btn-info" target="_blank" rel="noopener">
            Discuss this idea on GitHub <i className="fa-sharp fa-up-right-from-square"></i>
        </a>
    </div>
);

// The ideas in the sidebar, the chosen one in the wide panel over the map. On the compact layout
// the drawer shows one or the other, as the Blade page did on phones.
const Feedback = ({ issues, issue, canVote, loginUrl, bugReports }) => {
    const { compact } = useMapLayout();
    const showList = !compact || !issue;

    return (
        <>
            <Head title="Feedback" />
            <PageTitle title="Feedback" subtitle="Have your say on what gets built next" />

            {showList && <IdeaList issues={issues} current={issue?.number} bugReports={bugReports} />}

            {compact && issue && (
                <div className="container pb-0">
                    <Link href={route('feedback')} className="btn btn-outline-light btn-sm"><i className="fa-sharp fa-arrow-left"></i> All ideas</Link>
                </div>
            )}

            <WidePanel>
                {issue ? (
                    <Idea issue={issue} canVote={canVote} loginUrl={loginUrl} />
                ) : !compact && (
                    <div className="feedback-container action-text d-flex justify-content-center align-items-center">
                        <div className="text-center">
                            <p className="fs-2 m-0">Choose an idea from the sidebar for details and actions</p>
                        </div>
                    </div>
                )}
            </WidePanel>
        </>
    );
};

Feedback.wide = true;
Feedback.keepsMap = true;
Feedback.showMapHint = false;

export default Feedback;
