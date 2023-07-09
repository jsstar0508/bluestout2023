(()=>{"use strict";var e,t={227:()=>{const e=window.wp.blocks,t=window.wp.element,l=window.wp.i18n,a=window.wp.blockEditor,c=JSON.parse('{"u2":"customcskeystats/custom-cs-keystats"}');(0,e.registerBlockType)(c.u2,{edit:function({attributes:e,setAttributes:c}){const n=(0,a.useBlockProps)(),o=(t,l)=>{let a={...e};a[t]=l,c(a)};return(0,t.createElement)("div",{className:"block-wrapper",...n},(0,t.createElement)("div",{className:"block-title"},(0,t.createElement)("h2",null,"Key Stats:")),(0,t.createElement)("div",{className:"row-item"},(0,t.createElement)("div",{className:"col-item"},(0,t.createElement)(a.RichText,{...n,className:"d-content d-content-1",onChange:e=>o("content_1",e),allowedFormats:["core/bold","core/italic"],value:e.content_1,placeholder:(0,l.__)("Content 1")}),(0,t.createElement)(a.RichText,{...n,className:"d-title d-title-1",onChange:e=>o("title_1",e),allowedFormats:["core/bold","core/italic"],value:e.title_1,placeholder:(0,l.__)("Title 1")})),(0,t.createElement)("div",{className:"col-item"},(0,t.createElement)(a.RichText,{...n,className:"d-content d-content-2",onChange:e=>o("content_2",e),allowedFormats:["core/bold","core/italic"],value:e.content_2,placeholder:(0,l.__)("Content 2")}),(0,t.createElement)(a.RichText,{...n,className:"d-title d-title-2",onChange:e=>o("title_2",e),allowedFormats:["core/bold","core/italic"],value:e.title_2,placeholder:(0,l.__)("Title 2")})),(0,t.createElement)("div",{className:"col-item"},(0,t.createElement)(a.RichText,{...n,className:"d-content d-content-3",onChange:e=>o("content_3",e),allowedFormats:["core/bold","core/italic"],value:e.content_3,placeholder:(0,l.__)("Content 3")}),(0,t.createElement)(a.RichText,{...n,className:"d-title d-title-3",onChange:e=>o("title_3",e),allowedFormats:["core/bold","core/italic"],value:e.title_3,placeholder:(0,l.__)("Title 3")}))))},save:function({attributes:e}){const l=a.useBlockProps.save();return(0,t.createElement)("div",{className:"block-wrapper",...l},(0,t.createElement)("div",{className:"block-title"},(0,t.createElement)("h2",null,"Key Stats:")),(0,t.createElement)("div",{className:"row-item"},(0,t.createElement)("div",{className:"col-item"},(0,t.createElement)(a.RichText.Content,{...l,className:"d-content d-content-1",value:e.content_1,tagName:"p"}),(0,t.createElement)(a.RichText.Content,{...l,className:"d-title d-title-1",value:e.title_1,tagName:"p"})),(0,t.createElement)("div",{className:"col-item"},(0,t.createElement)(a.RichText.Content,{...l,className:"d-content d-content-2",value:e.content_2,tagName:"p"}),(0,t.createElement)(a.RichText.Content,{...l,className:"d-title d-title-2",value:e.title_2,tagName:"p"})),(0,t.createElement)("div",{className:"col-item"},(0,t.createElement)(a.RichText.Content,{...l,className:"d-content d-content-3",value:e.content_3,tagName:"p"}),(0,t.createElement)(a.RichText.Content,{...l,className:"d-title d-title-3",value:e.title_3,tagName:"p"}))))}})}},l={};function a(e){var c=l[e];if(void 0!==c)return c.exports;var n=l[e]={exports:{}};return t[e](n,n.exports,a),n.exports}a.m=t,e=[],a.O=(t,l,c,n)=>{if(!l){var o=1/0;for(m=0;m<e.length;m++){for(var[l,c,n]=e[m],r=!0,s=0;s<l.length;s++)(!1&n||o>=n)&&Object.keys(a.O).every((e=>a.O[e](l[s])))?l.splice(s--,1):(r=!1,n<o&&(o=n));if(r){e.splice(m--,1);var i=c();void 0!==i&&(t=i)}}return t}n=n||0;for(var m=e.length;m>0&&e[m-1][2]>n;m--)e[m]=e[m-1];e[m]=[l,c,n]},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={826:0,431:0};a.O.j=t=>0===e[t];var t=(t,l)=>{var c,n,[o,r,s]=l,i=0;if(o.some((t=>0!==e[t]))){for(c in r)a.o(r,c)&&(a.m[c]=r[c]);if(s)var m=s(a)}for(t&&t(l);i<o.length;i++)n=o[i],a.o(e,n)&&e[n]&&e[n][0](),e[n]=0;return a.O(m)},l=globalThis.webpackChunkcustom_cs_keystats=globalThis.webpackChunkcustom_cs_keystats||[];l.forEach(t.bind(null,0)),l.push=t.bind(null,l.push.bind(l))})();var c=a.O(void 0,[431],(()=>a(227)));c=a.O(c)})();