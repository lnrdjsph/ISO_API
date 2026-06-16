import "./bootstrap";

import Swal from 'sweetalert2';
window.Swal = Swal;

// NOTE: jQuery is intentionally NOT bundled here. It is loaded as a classic,
// synchronous <script> in the layout <head> so that $ is available to the many
// parse-time inline scripts (e.g. $(document).ready) across the app. Bundling it
// in this deferred module made $ undefined when those inline scripts ran.

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

import * as XLSX from 'xlsx';
window.XLSX = XLSX;

import SignaturePad from 'signature_pad';
window.SignaturePad = SignaturePad;

import "@fontsource/nunito/400.css";
import "@fontsource/nunito/600.css";
import "@fontsource/nunito/700.css";

import "./custom";

document.dispatchEvent(new Event('app:loaded'));



