import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule, NavigationEnd } from '@angular/router'; // ✅ Added RouterModule here
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Subscription } from 'rxjs';
import { filter } from 'rxjs/operators';

import { MenuService, MenuItem } from '@core/services/menu.service';
import { AuthService } from '@core/services/auth.service';

@Component({
  selector: 'app-navigation',
  standalone: true,
  imports: [
    CommonModule,
    MatIconModule,
    RouterModule,
    MatButtonModule,
    MatTooltipModule
  ],
  templateUrl: './navigation.component.html',
  styleUrls: ['./navigation.component.scss']
})
export class NavigationComponent implements OnInit, OnDestroy {
  menuItems: MenuItem[] = [];
  activeMenuId: string = '';
  menuLoaded: boolean = false;
  collapsed: boolean = false;

  private subscriptions: Subscription[] = [];

  constructor(
    private menuService: MenuService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.initializeNavigation();
    this.subscribeToRouterEvents();
    this.subscribeToMenuChanges();
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  private initializeNavigation(): void {
    // Load user menu on component initialization
    if (this.authService.isAuthenticated()) {
      this.loadUserMenu();
    }
  }

  private loadUserMenu(): void {
    const loadMenuSub = this.menuService.loadUserMenu().subscribe({
      next: (loaded: boolean) => {
        if (loaded) {
          this.menuLoaded = true;
          this.setActiveMenuFromCurrentRoute();
        }
      },
      error: (error) => {
        console.error('Failed to load navigation menu:', error);
      }
    });
    this.subscriptions.push(loadMenuSub);
  }

  private subscribeToMenuChanges(): void {
    // Subscribe to menu items changes
    const menuItemsSub = this.menuService.menuItems$.subscribe(items => {
      this.menuItems = items;
    });

    // Subscribe to active menu changes
    const activeMenuSub = this.menuService.activeMenu$.subscribe(activeId => {
      this.activeMenuId = activeId;
    });

    // Subscribe to menu loaded state
    const menuLoadedSub = this.menuService.menuLoaded$.subscribe(loaded => {
      this.menuLoaded = loaded;
    });

    this.subscriptions.push(menuItemsSub, activeMenuSub, menuLoadedSub);
  }

  private subscribeToRouterEvents(): void {
    // Listen to route changes to update active menu
    const routerSub = this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: NavigationEnd) => {
      this.setActiveMenuFromCurrentRoute();
    });

    this.subscriptions.push(routerSub);
  }

  private setActiveMenuFromCurrentRoute(): void {
    const currentRoute = this.router.url;
    const menuItem = this.menuService.findMenuByRoute(currentRoute);
    if (menuItem) {
      this.menuService.setActiveMenu(menuItem.id);
    }
  }

  // Handle menu item click
  onMenuClick(menuItem: MenuItem, event: Event): void {
    event.preventDefault();
    event.stopPropagation();

    // If menu has children, toggle expansion
    if (menuItem.children && menuItem.children.length > 0) {
      this.menuService.toggleMenuExpansion(menuItem.id);
      return;
    }

    // Navigate to menu route
    if (menuItem.route) {
      this.menuService.setActiveMenu(menuItem.id);
      this.router.navigate([menuItem.route]);
    } else if (menuItem.url) {
      // Handle legacy URL format
      this.menuService.setActiveMenu(menuItem.id);
      this.router.navigate([`/${menuItem.url.toLowerCase()}`]);
    }
  }

  // Check if menu item has permission
  hasPermission(menuItem: MenuItem): boolean {
    if (!menuItem.permission) {
      return true;
    }
    return this.authService.hasPermission(menuItem.permission);
  }

  // Check if menu item is active
  isMenuActive(menuItem: MenuItem): boolean {
    return menuItem.active || false;
  }

  // Check if menu item is expanded
  isMenuExpanded(menuItem: MenuItem): boolean {
    return menuItem.expanded || false;
  }

  // Get menu icon class
  getMenuIcon(menuItem: MenuItem): string {
    return menuItem.icon || 'pe-7s-menu';
  }

  // Handle navigation collapse/expand
  toggleNavigation(): void {
    this.collapsed = !this.collapsed;
  }

  // Get breadcrumb for current menu
  getBreadcrumb(): MenuItem[] {
    if (this.activeMenuId) {
      return this.menuService.getBreadcrumb(this.activeMenuId);
    }
    return [];
  }

  // Refresh menu data
  refreshMenu(): void {
    this.menuService.refreshMenu().subscribe({
      next: (loaded: boolean) => {
        if (loaded) {
          console.log('Menu refreshed successfully');
        }
      },
      error: (error) => {
        console.error('Failed to refresh menu:', error);
      }
    });
  }

  // Track by function for ngFor performance
  trackByMenuId(index: number, item: MenuItem): string {
    return item.id;
  }
}
