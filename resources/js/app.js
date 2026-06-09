import "./bootstrap";

import Swal from 'sweetalert2';
window.Swal = Swal;

import $ from 'jquery';
window.$ = window.jQuery = $;

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



