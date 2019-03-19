
export const ErrorMessages = ({errors}) => {

  return (
    <div className="messages messages--error">
      {errors.map((error, index) => {
        return (<div role="alert" key={index}>{error.message}</div>)
      })}
    </div>
  )
};
