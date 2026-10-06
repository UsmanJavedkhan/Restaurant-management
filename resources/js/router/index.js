import { createRouter, createWebHistory } from 'vue-router';
import { bootstrap, ready, user } from '../store';
const page=name=>()=>import(`../pages/${name}.vue`);
const admin=name=>()=>import(`../admin/${name}.vue`);
const router=createRouter({history:createWebHistory(),routes:[
{path:'/',component:page('Home')},{path:'/menu',component:page('Menu')},{path:'/deals',component:page('Menu')},{path:'/products/:id',component:page('ProductDetails')},{path:'/cart',component:page('Cart')},{path:'/checkout',component:page('Checkout'),meta:{auth:true}},
{path:'/login',component:page('Login')},{path:'/register',component:page('Register')},{path:'/admin/login',component:()=>import('../components/AuthForm.vue'),props:{mode:'admin'}},{path:'/forgot-password',component:()=>import('../components/AuthForm.vue'),props:{mode:'forgot'}},{path:'/reset-password/:token',component:()=>import('../components/AuthForm.vue'),props:{mode:'reset'}},
...['about','contact','privacy','terms'].map(name=>({path:'/'+name,component:page('Information'),props:{page:name}})),
{path:'/account',component:page('Account'),meta:{auth:true},children:[{path:'',component:page('AccountOverview')},{path:'orders',component:page('MyOrders')},{path:'orders/:id',component:page('OrderDetails')},{path:'addresses',component:page('Addresses')},{path:'profile',component:page('Profile')},{path:'password',component:page('Profile')},{path:'notifications',component:page('Notifications')}]},
{path:'/admin',component:admin('Layout'),meta:{auth:true,admin:true},children:[{path:'',component:admin('Dashboard')},{path:'reports',component:admin('Dashboard')},{path:'orders',component:admin('Orders')},{path:'orders/:id',component:page('OrderDetails')},{path:'customers',component:admin('Users')},{path:'payments',component:admin('Payments')},{path:'settings',component:admin('Settings')},{path:'notifications',component:page('Notifications')},...['categories','products','deals','coupons','delivery-areas'].map(resource=>({path:resource,component:admin('CatalogManager'),props:{resource}})),...['reservations','messages'].map(kind=>({path:kind,component:admin('Inbox'),props:{kind}}))]},
{path:'/:pathMatch(.*)*',redirect:'/'}],scrollBehavior(to){return to.hash?{el:to.hash,behavior:'smooth',top:100}:{top:0};}});
router.beforeEach(async to=>{if(!ready.value)await bootstrap();if(to.meta.auth&&!user.value)return {path:to.meta.admin?'/admin/login':'/login',query:{redirect:to.fullPath}};if(to.meta.admin&&user.value.role!=='admin')return '/account';});
export default router;
