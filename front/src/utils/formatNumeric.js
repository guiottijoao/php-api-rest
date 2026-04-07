export default function formatNumeric(num) {
  return Number.isInteger(Number(num)) ? Number(num) : num
}