import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { ApiService } from './api.service';
import { AuthService } from './auth.service';
import { environment } from '../../../environments/environment';

export interface MenuItem {
  id: string;
  name: string;
  icon: string;
  route?: string;
  url?: string;
  permission?: string;
  children?: MenuItem[];
  active?: boolean;
  expanded?: boolean;
  order?: number;
  module?: string;
  action?: string;
  params?: any[];
}

export interface MenuResponse {
  status: boolean;
  code: number;
  message: string;
  data?: MenuItem[];
  menus?: MenuItem[];
}

@Injectable({
  providedIn: 'root'
})
export class MenuService {
  private menuItemsSubject = new BehaviorSubject<MenuItem[]>([]);
  public menuItems$ = this.menuItemsSubject.asObservable();

  private activeMenuSubject = new BehaviorSubject<string>('');
  public activeMenu$ = this.activeMenuSubject.asObservable();

  private menuLoadedSubject = new BehaviorSubject<boolean>(false);
  public menuLoaded$ = this.menuLoadedSubject.asObservable();

  constructor(
    private http: HttpClient,
    private apiService: ApiService,
    private authService: AuthService
  ) {}

  // Preserve menuController::getUserMenu() logic from original AngularJS
  getUserMenu(userId?: string): Observable<MenuResponse> {
    const currentUser = this.authService.getCurrentUser();
    const userIdToUse = userId || currentUser?.id?.toString() || '';

    // Use original PHP endpoint pattern
    return this.apiService.get('/Menu/get_user_menus', { user_id: userIdToUse }).pipe(
      map((response: any) => {
        // Handle PHP response format similar to original
        if (response.status === 'success' || response.code === 200) {
          return {
            status: true,
            code: response.code || 200,
            message: response.message || 'Menu loaded successfully',
            data: this.processMenuData(response.data || response.menus || [])
          };
        } else {
          return {
            status: false,
            code: response.code || 100,
            message: response.message || 'Failed to load menu'
          };
        }
      }),
      catchError(error => {
        console.error('Menu loading error:', error);
        return of({
          status: false,
          code: 100,
          message: 'Network error occurred while loading menu'
        });
      })
    );
  }

  // Process menu data to match Angular structure
  private processMenuData(menuData: any[]): MenuItem[] {
    if (!Array.isArray(menuData)) {
      return [];
    }

    return menuData.map(item => ({
      id: item.id || item.menu_id || '',
      name: item.name || item.menu_name || item.txt_name || '',
      icon: item.icon || item.txt_icon || 'pe-7s-menu',
      route: this.buildRoute(item),
      url: item.url || item.txt_url || '',
      permission: item.permission || item.txt_permission || '',
      module: item.module || item.txt_module || '',
      action: item.action || item.txt_action || '',
      order: parseInt(item.order || item.int_order || '0'),
      active: false,
      expanded: false,
      children: item.children ? this.processMenuData(item.children) : []
    })).sort((a, b) => (a.order || 0) - (b.order || 0));
  }

  // Build Angular route from menu item data
  private buildRoute(item: any): string {
    if (item.route) {
      return item.route;
    }

    if (item.url || item.txt_url) {
      const url = item.url || item.txt_url;
      return `/${url.toLowerCase()}`;
    }

    if (item.module || item.txt_module) {
      const module = item.module || item.txt_module;
      return `/${module.toLowerCase()}`;
    }

    return '/dashboard';
  }

  // Load menu items based on user permissions - preserve original logic
  loadUserMenu(userId?: string): Observable<boolean> {
    return new Observable(observer => {
      this.getUserMenu(userId).subscribe({
        next: (response: MenuResponse) => {
          if (response.status && response.data) {
            // Filter menu items based on user permissions
            const filteredMenus = this.filterMenusByPermissions(response.data);
            this.menuItemsSubject.next(filteredMenus);
            this.menuLoadedSubject.next(true);
            observer.next(true);
          } else {
            console.error('Failed to load user menu:', response.message);
            this.menuLoadedSubject.next(false);
            observer.next(false);
          }
          observer.complete();
        },
        error: (error) => {
          console.error('Menu loading error:', error);
          this.menuLoadedSubject.next(false);
          observer.next(false);
          observer.complete();
        }
      });
    });
  }

  // Filter menus based on user permissions - preserve Perm_Auth::verifyPermission() logic
  private filterMenusByPermissions(menus: MenuItem[]): MenuItem[] {
    return menus.filter(menu => {
      // Check if user has permission for this menu item
      if (menu.permission && !this.authService.hasPermission(menu.permission)) {
        return false;
      }

      // Recursively filter children
      if (menu.children && menu.children.length > 0) {
        menu.children = this.filterMenusByPermissions(menu.children);
        // Keep parent if it has visible children or no permission requirement
        return menu.children.length > 0 || !menu.permission;
      }

      return true;
    });
  }

  // Get current menu items
  getMenuItems(): MenuItem[] {
    return this.menuItemsSubject.value;
  }

  // Set active menu item
  setActiveMenu(menuId: string): void {
    const menus = this.getMenuItems();
    this.updateActiveMenu(menus, menuId);
    this.menuItemsSubject.next([...menus]);
    this.activeMenuSubject.next(menuId);
  }

  // Update active menu state recursively
  private updateActiveMenu(menus: MenuItem[], activeId: string): void {
    menus.forEach(menu => {
      menu.active = menu.id === activeId;
      if (menu.children) {
        this.updateActiveMenu(menu.children, activeId);
        // Expand parent if child is active
        if (menu.children.some(child => child.active)) {
          menu.expanded = true;
        }
      }
    });
  }

  // Toggle menu expansion
  toggleMenuExpansion(menuId: string): void {
    const menus = this.getMenuItems();
    this.toggleExpansion(menus, menuId);
    this.menuItemsSubject.next([...menus]);
  }

  private toggleExpansion(menus: MenuItem[], targetId: string): void {
    menus.forEach(menu => {
      if (menu.id === targetId) {
        menu.expanded = !menu.expanded;
      }
      if (menu.children) {
        this.toggleExpansion(menu.children, targetId);
      }
    });
  }

  // Find menu item by route
  findMenuByRoute(route: string): MenuItem | null {
    const menus = this.getMenuItems();
    return this.searchMenuByRoute(menus, route);
  }

  private searchMenuByRoute(menus: MenuItem[], route: string): MenuItem | null {
    for (const menu of menus) {
      if (menu.route === route) {
        return menu;
      }
      if (menu.children) {
        const found = this.searchMenuByRoute(menu.children, route);
        if (found) return found;
      }
    }
    return null;
  }

  // Get breadcrumb path for current menu
  getBreadcrumb(menuId: string): MenuItem[] {
    const menus = this.getMenuItems();
    const path: MenuItem[] = [];
    this.findMenuPath(menus, menuId, path);
    return path;
  }

  private findMenuPath(menus: MenuItem[], targetId: string, path: MenuItem[]): boolean {
    for (const menu of menus) {
      path.push(menu);
      if (menu.id === targetId) {
        return true;
      }
      if (menu.children && this.findMenuPath(menu.children, targetId, path)) {
        return true;
      }
      path.pop();
    }
    return false;
  }

  // Refresh menu data
  refreshMenu(): Observable<boolean> {
    return this.loadUserMenu();
  }

  // Check if menu is loaded
  isMenuLoaded(): boolean {
    return this.menuLoadedSubject.value;
  }

  // Get active menu ID
  getActiveMenuId(): string {
    return this.activeMenuSubject.value;
  }

  // Clear menu data (for logout)
  clearMenu(): void {
    this.menuItemsSubject.next([]);
    this.activeMenuSubject.next('');
    this.menuLoadedSubject.next(false);
  }
}
