import{l as r,C as n}from"./index-BVx_8k6t.js";/**
 * @license lucide-vue-next v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const y=r("CopyIcon",[["rect",{width:"14",height:"14",x:"8",y:"8",rx:"2",ry:"2",key:"17jyea"}],["path",{d:"M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2",key:"zix9uf"}]]);function i(){const t=n();async function a(c,o="Copied to clipboard"){try{await navigator.clipboard.writeText(String(c??"")),t.success(o)}catch{const e=document.createElement("textarea");e.value=String(c??""),document.body.appendChild(e),e.select();try{document.execCommand("copy"),t.success(o)}catch{t.error("Could not copy. Please copy manually.")}e.remove()}}return{copy:a}}export{y as C,i as u};
