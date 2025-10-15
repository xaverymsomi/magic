import { Component, Input, Output, EventEmitter, OnInit, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router, NavigationEnd } from '@angular/router';
import { AuthService } from '@core/services/auth.service';
import { User } from '@shared/models/auth.models';
import { filter } from 'rxjs/operators';

export interface Breadcrumb {
  label: string;
  route?: string;
}

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {
  @Input() sidebarCollapsed = false;
  @Output() toggleSidebar = new EventEmitter<void>();

  currentUser: User | null = null;
  pageTitle = 'Dashboard';
  breadcrumbs: Breadcrumb[] = [];
  showUserDropdown = false;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Get current user
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
    });

    // Listen to route changes for page title and breadcrumbs
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.updatePageInfo();
    });

    // Initialize page info
    this.updatePageInfo();
  }

  // Close dropdown when clicking outside
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: Event): void {
    const target = event.target as HTMLElement;
    if (!target.closest('.user-profile')) {
      this.showUserDropdown = false;
    }
  }

  onToggleSidebar(): void {
    this.toggleSidebar.emit();
  }

  toggleUserDropdown(): void {
    this.showUserDropdown = !this.showUserDropdown;
  }

  viewProfile(): void {
    this.showUserDropdown = false;
    // Navigate to profile page or open profile modal
    console.log('View profile');
  }

  changePassword(): void {
    this.showUserDropdown = false;
    // Open change password modal
    console.log('Change password');
  }

  logout(): void {
    this.showUserDropdown = false;
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']);
      },
      error: (error) => {
        console.error('Logout error:', error);
        // Force navigation even if logout API fails
        this.router.navigate(['/login']);
      }
    });
  }

  private updatePageInfo(): void {
    const url = this.router.url;

    // Update page title based on current route
    if (url === '/dashboard' || url === '/') {
      this.pageTitle = 'Dashboard';
      this.breadcrumbs = [];
    } else if (url.startsWith('/User')) {
      this.pageTitle = 'User Management';
      this.breadcrumbs = [
        { label: 'Home', route: '/dashboard' },
        { label: 'Users' }
      ];
    } else if (url.startsWith('/Report')) {
      this.pageTitle = 'Reports';
      this.breadcrumbs = [
        { label: 'Home', route: '/dashboard' },
        { label: 'Reports' }
      ];
    } else if (url.startsWith('/Miscellaneous')) {
      this.pageTitle = 'Miscellaneous';
      this.breadcrumbs = [
        { label: 'Home', route: '/dashboard' },
        { label: 'Miscellaneous' }
      ];
    } else {
      // Get title from localStorage (matching AngularJS pattern)
      const storedTitle = localStorage.getItem('CurrentPageTitle');
      this.pageTitle = storedTitle || 'Dashboard';
      this.breadcrumbs = storedTitle ? [
        { label: 'Home', route: '/dashboard' },
        { label: storedTitle }
      ] : [];
    }
  }
}
