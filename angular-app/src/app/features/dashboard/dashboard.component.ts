import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '@core/services/auth.service';
import { DashboardService, DashboardData } from '@core/services/dashboard.service';
import { User } from '@shared/models/auth.models';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit {
  currentUser: User | null = null;
  dashboardData: DashboardData | null = null;
  loading: boolean = true;
  error: string = '';

  constructor(
    private authService: AuthService,
    private dashboardService: DashboardService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Preserve Auth::checkLogin() logic
    if (!this.authService.isAuthenticated()) {
      this.router.navigate(['/login']);
      return;
    }

    this.currentUser = this.authService.getCurrentUser();
    this.loadDashboardData();
  }

  // Preserve dashboard data loading logic from Dashboard::fetch_dashboard_*_data()
  private loadDashboardData(): void {
    if (!this.currentUser) return;

    this.loading = true;
    this.error = '';

    // Determine which dashboard data to load based on user permissions
    if (this.hasPermission('dashboard_admin')) {
      this.dashboardService.fetchDashboardAdminData().subscribe({
        next: (data: DashboardData) => {
          this.dashboardData = data;
          this.loading = false;
        },
        error: (error: any) => {
          this.error = 'Failed to load dashboard data';
          this.loading = false;
        }
      });
    } else if (this.hasPermission('dashboard_medical')) {
      this.dashboardService.fetchDashboardMedicalData().subscribe({
        next: (data: DashboardData) => {
          this.dashboardData = data;
          this.loading = false;
        },
        error: (error: any) => {
          this.error = 'Failed to load dashboard data';
          this.loading = false;
        }
      });
    } else {
      // Default dashboard or permission denied
      this.error = 'Access denied: Insufficient permissions';
      this.loading = false;
    }
  }

  // Preserve Perm_Auth::verifyPermission() logic
  hasPermission(permission: string): boolean {
    return this.authService.hasPermission(permission);
  }

  // Preserve Dashboard::create_new_ticket() logic
  createNewTicket(): void {
    if (this.hasPermission('create_ticket')) {
      // Navigate to ticket creation or open modal
      // This will be implemented when we migrate the ticket module
      console.log('Create new ticket functionality');
    } else {
      this.error = 'Permission denied: Cannot create tickets';
    }
  }

  // Handle logout
  logout(): void {
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
}
