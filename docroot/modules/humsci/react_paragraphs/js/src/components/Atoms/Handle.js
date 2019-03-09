import styled from 'styled-components';
import icon from '../../icons/move.svg';

const Wrapper = styled.div`
  text-align: center;
  width: 20px;
  flex: 20px 0 0;
  background-color: #edede8;
`;

const HandleIcon = styled.span`
  margin-top: 0;
  display: block;
  height: 100%;
  font-size: 0;
  background: url(${icon}) no-repeat center;
`;

export const Handle = (props) => {
  return (
    <Wrapper  {...props} className="draggable-handle">
      <HandleIcon>::
        <span className="visually-hidden">Move this item</span>
      </HandleIcon>
    </Wrapper>
  )
};
