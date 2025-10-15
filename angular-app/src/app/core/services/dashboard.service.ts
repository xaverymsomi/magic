import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { ApiService } from './api.service';

export interface DashboardStatistic {
  icon: string;
  value: string | number;
  label: string;
}

export interface DashboardActivity {
  time: string;
  description: string;
}

export interface DashboardData {
  statistics?: DashboardStatistic[];
  recentActivities?: DashboardActivity[];
  chartData?: any;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardService {

  constructor(private apiService: ApiService) {}

  // Preserve Dashboard::fetch_dashboard_admin_data() business logic
  fetchDashboardAdminData(): Observable<DashboardData> {
    return this.apiService.post('/Dashboard/fetch_dashboard_admin_data', {}).pipe(
      map((response: any) => {
        // Transform PHP response to Angular-friendly format
        return this.transformDashboardData(response);
      })
    );
  }

  // Preserve Dashboard::fetch_dashboard_medical_data() business logic
  fetchDashboardMedicalData(): Observable<DashboardData> {
    return this.apiService.post('/Dashboard/fetch_dashboard_medical_data', {}).pipe(
      map((response: any) => {
        // Transform PHP response to Angular-friendly format
        return this.transformDashboardData(response);
      })
    );
  }

  // Preserve Dashboard::create_new_ticket() business logic
  createNewTicket(ticketData: any): Observable<any> {
    return this.apiService.post('/Dashboard/create_new_ticket', ticketData);
  }

  // Get dashboard controls (preserving Dashboard_Model::getControls())
  getDashboardControls(): Observable<any[]> {
    return this.apiService.get('/Dashboard/get_controls').pipe(
      map((response: any) => {
        return response.controls || [];
      })
    );
  }

  // Transform PHP dashboard data to Angular format
  private transformDashboardData(response: any): DashboardData {
    const dashboardData: DashboardData = {};

    // Handle statistics
    if (response.statistics) {
      dashboardData.statistics = response.statistics.map((stat: any) => ({
        icon: this.getIconClass(stat.type || stat.icon),
        value: stat.value || stat.count || 0,
        label: stat.label || stat.title || stat.name
      }));
    }

    // Handle recent activities
    if (response.activities || response.recent_activities) {
      const activities = response.activities || response.recent_activities;
      dashboardData.recentActivities = activities.map((activity: any) => ({
        time: this.formatTime(activity.created_at || activity.time || activity.date),
        description: activity.description || activity.message || activity.title
      }));
    }

    // Handle chart data
    if (response.chart_data || response.charts) {
      dashboardData.chartData = response.chart_data || response.charts;
    }

    return dashboardData;
  }

  // Map data types to FontAwesome icon classes
  private getIconClass(type: string): string {
    const iconMap: { [key: string]: string } = {
      'users': 'fas fa-users',
      'tickets': 'fas fa-ticket-alt',
      'revenue': 'fas fa-dollar-sign',
      'orders': 'fas fa-shopping-cart',
      'patients': 'fas fa-user-injured',
      'appointments': 'fas fa-calendar-check',
      'reports': 'fas fa-chart-bar',
      'notifications': 'fas fa-bell',
      'messages': 'fas fa-envelope',
      'tasks': 'fas fa-tasks',
      'default': 'fas fa-info-circle'
    };

    return iconMap[type.toLowerCase()] || iconMap['default'];
  }

  // Format timestamp to readable time
  private formatTime(timestamp: string): string {
    if (!timestamp) return '';
    
    try {
      const date = new Date(timestamp);
      const now = new Date();
      const diffMs = now.getTime() - date.getTime();
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);

      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      if (diffDays < 7) return `${diffDays}d ago`;
      
      return date.toLocaleDateString();
    } catch (error) {
      return timestamp;
    }
  }
}
