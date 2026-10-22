import "./bootstrap";
import { Fancybox } from "@fancyapps/ui";
import "@fancyapps/ui/dist/fancybox/fancybox.css";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css"; // or another theme if preferred
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect";
import "flatpickr/dist/plugins/monthSelect/style.css";
import "/node_modules/select2/dist/css/select2.css";
import DataTable from "datatables.net";
import Chart from "chart.js/auto";

window.Fancybox = Fancybox;
window.TomSelect = TomSelect;
window.flatpickr = flatpickr;
window.monthSelectPlugin = monthSelectPlugin;
window.Chart = Chart;

