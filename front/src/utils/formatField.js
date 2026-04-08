import formatNumeric from './formatNumeric.js'

export default function formatField(col, value) {
  switch (col.format) {
    case "percent":
      return `${formatNumeric(value)}%`
    case "currency":
      return `$${Number(value).toFixed(2)}`;
    default:
      return value;
  }
}
