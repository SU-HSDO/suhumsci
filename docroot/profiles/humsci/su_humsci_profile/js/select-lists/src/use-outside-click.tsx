import {useEffect, useRef} from "preact/compat";

const useOutsideClick = (onClickOutside: (e: Event) => void) => {

  const clickCaptured = useRef(false)
  const focusCaptured = useRef(false)

  const documentClick = (event: Event) => {
    if (!clickCaptured.current && onClickOutside) {
      setTimeout(() => {
        onClickOutside(event);
      }, 10);
    }
    clickCaptured.current = false;
  }

  const documentFocus = (event: Event) => {
    if (!focusCaptured.current && onClickOutside) {
      setTimeout(() => {
        onClickOutside(event);
      }, 10);
    }
    focusCaptured.current = false;
  }

  useEffect(() => {
    document.addEventListener("mousedown", documentClick);
    document.addEventListener("focusin", documentFocus);
    document.addEventListener("touchstart", documentClick);
    return () => {
      document.removeEventListener("mousedown", documentClick);
      document.removeEventListener("focusin", documentFocus);
      document.removeEventListener("touchstart", documentClick);
    }
  }, [])
  return {
    onMouseDown: () => clickCaptured.current = true,
    onFocus: () => focusCaptured.current = true,
    onTouchStart: () => clickCaptured.current = true
  }
}

export default useOutsideClick;
