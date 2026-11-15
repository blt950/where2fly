const FieldError = ({ message, className = 'validation-error', icon = true }) => (message
    ? <div className={className}>{icon && <><i className="fa-sharp fa-exclamation-triangle"></i> </>}{message}</div>
    : null);

export default FieldError;
