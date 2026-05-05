import { h } from 'preact';

export default function BasicText({ title, text }) {
  return (
    <div className="basic-text-component p-4 border rounded bg-white">
      {title && <h2 className="text-xl font-bold mb-2">{title}</h2>}
      {text && <div className="text-base whitespace-pre-line">{text}</div>}
    </div>
  );
}
