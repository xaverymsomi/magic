import { Routes } from '@angular/router';
import { AuthGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  // Default route - redirect to dashboard if authenticated, otherwise to login
  {
    path: '',
    redirectTo: '/dashboard',
    pathMatch: 'full'
  },

  // Login route - no guard needed, accessible to everyone
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login.component').then(m => m.LoginComponent)
  },

  // Protected routes - require authentication
  {
    path: 'dashboard',
    canActivate: [AuthGuard],
    loadComponent: () => import('./shared/layouts/main-layout/main-layout.component').then(m => m.MainLayoutComponent),
    children: [
      {
        path: '',
        loadComponent: () => import('./features/dashboard/dashboard.component').then(m => m.DashboardComponent)
      }
    ]
  },

  // Catch-all route - redirect to dashboard
  {
    path: '**',
    redirectTo: '/dashboard'
  }
];
