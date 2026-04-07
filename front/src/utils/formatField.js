import formatNumeric from './formatNumeric.js'

export default function formatField(col, value) {
  switch (col.format) {
    case "percent":
      return `${formatNumeric(value)}%`
    case "currency":
      return `$${value}`;
    default:
      return value;
  }
}
