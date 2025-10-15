import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { AuthService } from '@core/services/auth.service';
import { MenuService } from '@core/services/menu.service';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {
  @Input() isCollapsed = false;
  @Output() toggleSidebar = new EventEmitter<void>();

  activeSubmenu: string | null = null;
  currentRoute = '';

  constructor(
    private authService: AuthService,
    private menuService: MenuService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Track current route for active state
    this.router.events.subscribe(() => {
      this.currentRoute = this.router.url;
    });
  }

  // Preserve Perm_Auth::verifyPermission() logic
  hasPermission(permission: string): boolean {
    return this.authService.hasPermission(permission);
  }

  // Preserve menuController::loadPage() logic
  navigateToPage(link: string, title: string, id: string): void {
    // Store current page info (matching AngularJS localStorage usage)
    localStorage.setItem('CurrentLink', link);
    localStorage.setItem('CurrentPageTitle', title);
    localStorage.setItem('CurrentLinkId', id);

    // Navigate using Angular router
    this.router.navigate([link]);

    // Close submenu on mobile
    if (window.innerWidth <= 768) {
      this.toggleSidebar.emit();
    }
  }

  // Preserve formController::showForm() logic
  openForm(url: string, action: string, params: any[] = []): void {
    // This will be implemented when we migrate the form service
    console.log(`Opening form: ${url}/${action}`, params);
    // TODO: Implement form modal opening logic
  }

  toggleSubmenu(menu: string): void {
    if (this.isCollapsed) return;

    this.activeSubmenu = this.activeSubmenu === menu ? null : menu;
  }

  onToggleSidebar(): void {
    this.toggleSidebar.emit();
  }

  isActiveRoute(route: string): boolean {
    return this.currentRoute === route || this.currentRoute.startsWith(route + '/');
  }
}
