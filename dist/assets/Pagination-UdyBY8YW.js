import{l as g,o,c as l,a,k as m,t as n,g as i,b as c,e as d}from"./index-BVx_8k6t.js";/**
 * @license lucide-vue-next v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const b=g("ChevronLeftIcon",[["path",{d:"m15 18-6-6 6-6",key:"1wnfg3"}]]);/**
 * @license lucide-vue-next v0.460.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const u=g("ChevronRightIcon",[["path",{d:"m9 18 6-6-6-6",key:"mthhwq"}]]),h={key:0,class:"mt-4 flex items-center justify-between text-sm","aria-label":"Pagination"},f={key:0},y={class:"flex gap-2"},k=["disabled"],v=["disabled"],P={__name:"Pagination",props:{page:{type:Number,default:1},lastPage:{type:Number,default:1},total:{type:Number,default:0}},emits:["change"],setup(e){return(s,t)=>e.lastPage>1?(o(),l("nav",h,[a("span",null,[m("Page "+n(e.page)+" of "+n(e.lastPage)+" ",1),e.total?(o(),l("span",f,"· "+n(e.total)+" items",1)):i("",!0)]),a("div",y,[a("button",{class:"btn btn-secondary !px-3",disabled:e.page<=1,"aria-label":"Previous page",onClick:t[0]||(t[0]=r=>s.$emit("change",e.page-1))},[c(d(b),{class:"h-4 w-4"})],8,k),a("button",{class:"btn btn-secondary !px-3",disabled:e.page>=e.lastPage,"aria-label":"Next page",onClick:t[1]||(t[1]=r=>s.$emit("change",e.page+1))},[c(d(u),{class:"h-4 w-4"})],8,v)])])):i("",!0)}};export{P as _};
