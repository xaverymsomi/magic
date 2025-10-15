import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { HeaderComponent } from '../header/header.component';
import { SidebarComponent } from '../sidebar/sidebar.component';
import { FooterComponent } from '../footer/footer.component';
import { AuthService } from '../../../core/services/auth.service';
import { User } from '../../models/auth.models';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    HeaderComponent,
    SidebarComponent,
    FooterComponent
  ],
  template: `
    <div class="main" name="main" [class.sidebar-collapsed]="sidebarCollapsed">
      <!-- Sidebar Navigation -->
      <app-sidebar 
        [isCollapsed]="sidebarCollapsed"
        [user]="currentUser">
      </app-sidebar>

      <!-- Header/Top Navigation -->
      <app-header 
        [user]="currentUser"
        (menuToggle)="toggleSidebar()"
        (logout)="handleLogout()">
      </app-header>

      <!-- Main Content Area -->
      <div class="container-fluid">
        <div id="mabrexPageContentHolder" class="row">
          <router-outlet></router-outlet>
        </div>
      </div>

      <!-- Loading Overlay -->
      <div class="overlay" [class.hidden]="!isLoading">
        <div class="overlay__inner">
          <div class="overlay__content">
            <span class="spinner"></span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <app-footer></app-footer>
    </div>
  `,
  styleUrls: ['./main-layout.component.scss']
})
export class MainLayoutComponent implements OnInit {
  currentUser: User | null = null;
  sidebarCollapsed = false;
  isLoading = false;

  constructor(private authService: AuthService) {}

  ngOnInit(): void {
    // Subscribe to current user
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
    });
  }

  toggleSidebar(): void {
    this.sidebarCollapsed = !this.sidebarCollapsed;
  }

  handleLogout(): void {
    this.authService.logout().subscribe(() => {
      // Logout handled by auth service
    });
  }
}
