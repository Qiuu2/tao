import { RouteRecordRaw } from "vue-router";

import { HOME_URL, LOGIN_URL } from "@/config";
import i18n from "@/languages";

// 这里在组件外面，取不到 setup 里的 t() —— 用 i18n 实例上的全局 t。
const t = i18n.global.t;

/**
 * staticRouter (静态路由)
 */
export const staticRouter: RouteRecordRaw[] = [
  {
    path: "/",
    redirect: HOME_URL
  },
  {
    path: LOGIN_URL,
    name: "login",
    component: () => import("@/views/login/index.vue"),
    meta: {
      title: t("route.login")
    }
  },
  {
    /*
      注册服务的**登录前**入口。

      auth.Login 里有一条硬规矩：registerflag 不是 1（已注册）或 2（试用中）
      就禁止登录（BR-71）。所以没注册的服务器根本进不了后台，
      注册页必须能在登录前打开 —— 旧版 login.php 也正是在这种状态下
      直接跳到 regist_server.php。

      登录之后还有一份带侧边栏的同页面，路径是 /user/register（菜单里那一项）。
    */
    path: "/register",
    name: "registerServerStandalone",
    component: () => import("@/views/register/index.vue"),
    meta: {
      title: t("route.register")
    }
  },
  {
    path: "/layout",
    name: "layout",
    component: () => import("@/layouts/index.vue"),
    // component: () => import("@/layouts/indexAsync.vue"),
    redirect: HOME_URL,
    children: []
  }
];

/**
 * errorRouter (错误页面路由)
 */
export const errorRouter = [
  {
    path: "/403",
    name: "403",
    component: () => import("@/components/ErrorMessage/403.vue"),
    meta: {
      title: t("route.p403")
    }
  },
  {
    path: "/404",
    name: "404",
    component: () => import("@/components/ErrorMessage/404.vue"),
    meta: {
      title: t("route.p404")
    }
  },
  {
    path: "/500",
    name: "500",
    component: () => import("@/components/ErrorMessage/500.vue"),
    meta: {
      title: t("route.p500")
    }
  },
  // Resolve refresh page, route warnings
  {
    path: "/:pathMatch(.*)*",
    component: () => import("@/components/ErrorMessage/404.vue")
  }
];
