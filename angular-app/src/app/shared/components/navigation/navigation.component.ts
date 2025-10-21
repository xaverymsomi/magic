import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, NavigationEnd } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatTooltipModule } from '@angular/material/tooltip';
import { RouterModule } from '@angular/router';
import { Subscription } from 'rxjs';
import { filter } from 'rxjs/operators';

import { MenuService, MenuItem } from '../../../core/services/menu.service';

@Component({
  selector: 'app-navigation',
  standalone: true,
  imports: [
    CommonModule,
    MatIconModule,
    MatButtonModule,
    MatTooltipModule,
    RouterModule
  ],
  templateUrl: './navigation.component.html',
  styleUrls: ['./navigation.component.scss']
})
export class NavigationComponent implements OnInit, OnDestroy {
  collapsed = false;
  menuItems: MenuItem[] = [];
  menuLoaded = false;
  currentRoute = '';

  private subscriptions: Subscription[] = [];

  // Fallback menu items matching the original design
  private fallbackMenuItems: MenuItem[] = [
    {
      id: 'home',
      name: 'Home',
      icon: 'pe-7s-home',
      route: '/dashboard',
      order: 1
    },
    {
      id: 'users',
      name: 'Users',
      icon: 'pe-7s-users',
      route: '/users',
      order: 2,
      children: [
        {
          id: 'users-list',
          name: 'User List',
          icon: 'pe-7s-user',
          route: '/users/list'
        },
        {
          id: 'users-create',
          name: 'Create User',
          icon: 'pe-7s-add-user',
          route: '/users/create'
        }
      ]
    },
    {
      id: 'reports',
      name: 'Reports',
      icon: 'pe-7s-graph1',
      route: '/reports',
      order: 3
    },
    {
      id: 'miscellaneous',
      name: 'Miscellaneous',
      icon: 'pe-7s-config',
      route: '/miscellaneous',
      order: 4
    },
    {
      id: 'utility',
      name: 'Utility',
      icon: 'pe-7s-tools',
      route: '/utility',
      order: 5,
      children: [
        {
          id: 'utility-backup',
          name: 'Database Backup',
          icon: 'pe-7s-cloud-download',
          route: '/utility/backup'
        },
        {
          id: 'utility-settings',
          name: 'System Settings',
          icon: 'pe-7s-settings',
          route: '/utility/settings'
        }
      ]
    }
  ];

  constructor(
    private menuService: MenuService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.initializeNavigation();
    this.subscribeToRouteChanges();
    this.subscribeToMenuService();
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  private initializeNavigation(): void {
    // Try to load user menu, fallback to default menu
    this.menuService.loadUserMenu().subscribe({
      next: (success) => {
        if (success) {
          this.menuLoaded = true;
        } else {
          // Use fallback menu items if loading fails
          this.menuItems = [...this.fallbackMenuItems];
          this.menuLoaded = true;
          console.log('Using fallback menu items');
        }
      },
      error: (error) => {
        console.error('Failed to load menu, using fallback:', error);
        this.menuItems = [...this.fallbackMenuItems];
        this.menuLoaded = true;
      }
    });
  }

  private subscribeToRouteChanges(): void {
    const routeSubscription = this.router.events
      .pipe(filter(event => event instanceof NavigationEnd))
      .subscribe((event: NavigationEnd) => {
        this.currentRoute = event.url;
        this.updateActiveMenuFromRoute();
      });

    this.subscriptions.push(routeSubscription);
  }

  private subscribeToMenuService(): void {
    const menuSubscription = this.menuService.menuItems$.subscribe(items => {
      if (items && items.length > 0) {
        this.menuItems = items;
        this.updateActiveMenuFromRoute();
      }
    });

    const loadedSubscription = this.menuService.menuLoaded$.subscribe(loaded => {
      this.menuLoaded = loaded;
    });

    this.subscriptions.push(menuSubscription, loadedSubscription);
  }

  private updateActiveMenuFromRoute(): void {
    const activeMenu = this.menuService.findMenuByRoute(this.currentRoute);
    if (activeMenu) {
      this.menuService.setActiveMenu(activeMenu.id);
    }
  }

  toggleNavigation(): void {
    this.collapsed = !this.collapsed;
  }

  onMenuClick(menuItem: MenuItem, event: Event): void {
    event.preventDefault();

    // Handle menu items with children
    if (menuItem.children && menuItem.children.length > 0) {
      this.menuService.toggleMenuExpansion(menuItem.id);
      return;
    }

    // Handle navigation
    if (menuItem.route) {
      this.menuService.setActiveMenu(menuItem.id);
      this.router.navigate([menuItem.route]);
    } else if (menuItem.url) {
      // Handle external URLs or legacy PHP routes
      window.location.href = menuItem.url;
    }
  }

  isMenuActive(menuItem: MenuItem): boolean {
    if (menuItem.active) return true;

    // Check if current route matches menu route
    if (menuItem.route && this.currentRoute.startsWith(menuItem.route)) {
      return true;
    }

    // Check if any child is active
    if (menuItem.children) {
      return menuItem.children.some(child => this.isMenuActive(child));
    }

    return false;
  }

  isMenuExpanded(menuItem: MenuItem): boolean {
    return menuItem.expanded || false;
  }

  getMenuIcon(menuItem: MenuItem): string {
    // Map PE7 icons to Material Icons or use default
    const iconMap: { [key: string]: string } = {
      'pe-7s-home': 'home',
      'pe-7s-users': 'people',
      'pe-7s-user': 'person',
      'pe-7s-add-user': 'person_add',
      'pe-7s-graph1': 'assessment',
      'pe-7s-config': 'settings',
      'pe-7s-tools': 'build',
      'pe-7s-cloud-download': 'cloud_download',
      'pe-7s-settings': 'tune',
      'pe-7s-menu': 'menu'
    };

    return iconMap[menuItem.icon] || 'circle';
  }

  refreshMenu(): void {
    this.menuLoaded = false;
    this.menuService.refreshMenu().subscribe({
      next: (success) => {
        if (!success) {
          // Fallback to default menu if refresh fails
          this.menuItems = [...this.fallbackMenuItems];
        }
        this.menuLoaded = true;
      },
      error: () => {
        this.menuItems = [...this.fallbackMenuItems];
        this.menuLoaded = true;
      }
    });
  }

  getBreadcrumb(): MenuItem[] {
    const activeMenuId = this.menuService.getActiveMenuId();
    if (activeMenuId) {
      return this.menuService.getBreadcrumb(activeMenuId);
    }
    return [];
  }

  trackByMenuId(index: number, item: MenuItem): string {
    return item.id;
  }
}
