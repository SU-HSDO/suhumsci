export const Handle = (props) => {
  return (
    <div  {...props} className="draggable-handle"><span className="handle-icon">::
      <span className="visually-hidden">Move this item</span>
    </span></div>
  )
};
