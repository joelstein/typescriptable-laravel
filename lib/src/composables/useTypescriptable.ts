import { usePage } from '@inertiajs/vue3'
import { RouteList, Router } from '@/methods'
import type { RequestPayload } from '@/types'

export function useTypescriptable() {
  const list = RouteList.make()

  const router = {
    get(name: App.Route.Name): void {
      return Router.make().get(name)
    },
    post(name: App.Route.Name, data?: RequestPayload): void {
      return Router.make().post(name, data)
    },
    put(name: App.Route.Name, data?: RequestPayload): void {
      return Router.make().put(name, data)
    },
    patch(name: App.Route.Name, data?: RequestPayload): void {
      return Router.make().patch(name, data)
    },
    delete(name: App.Route.Name): void {
      return Router.make().delete(name)
    },
  }

  const page = usePage<Inertia.PageProps>()

  const isRoute = (route: App.Route.Name): boolean => {
    const list = RouteList.make()
    const url = list.getCurrentUrl()
    const currentRoute = list.getRouteFromUrl(url)

    const current: string = route
    if (currentRoute) {
      const currentRouteName: string = currentRoute.name
      const currentRoutePath: string = currentRoute.path

      if (currentRouteName === current)
        return true

      if (currentRoutePath === current)
        return true
    }

    return false
  }

  function isDev(): boolean {
    return process.env.NODE_ENV === 'development'
  }

  function currentRoute(): App.Route.Link | undefined {
    const url = list.getCurrentUrl()
    return list.getRouteFromUrl(url)
  }

  function route<T extends App.Route.Name>(name: T, params?: T extends keyof App.Route.Params ? App.Route.Params[T] : never): string {
    const item = RouteList.make()
    return item.getRouteBind({
      name,
      params,
    })
  }

  function to<T extends App.Route.Name>(route: App.Route.RouteConfig<T>): string {
    const item = RouteList.make()
    return item.getRouteBind(route)
  }

  return {
    router,
    isRoute,
    page,
    isDev,
    currentRoute,
    route,
    to,
  }
}
