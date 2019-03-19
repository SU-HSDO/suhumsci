import styled from 'styled-components'

const MessageWrapper = styled.div`
  padding: 20px;
  width:100%;
  color: #a51b00;
  background: #fcf4f2;
  border-color: #f9c9bf #f9c9bf #f9c9bf transparent;
  box-shadow: -8px 0 0 #e62600;
`;

export const ErrorMessages = ({errors}) => {

  return (
    <MessageWrapper>
      {errors.map((error, index) => {
        return (<div key={index}>{error.message}</div>)
      })}
    </MessageWrapper>
  )
}
