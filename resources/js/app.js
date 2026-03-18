import "./bootstrap";
// console.log("Laravel app loaded");

// document.addEventListener("DOMContentLoaded", function () {
//     console.log("Vite + Tailwind 4.1 is working!");
// });
import Swal from 'sweetalert2';
window.Swal = Swal;

import $ from 'jquery';
window.$ = window.jQuery = $;

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;


import "./custom"; // Import your custom JS file

document.dispatchEvent(new Event('app:loaded'));



