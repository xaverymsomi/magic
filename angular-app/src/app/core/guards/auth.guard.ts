import { Injectable } from '@angular/core';
import { CanActivate, Router, ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';
import { Observable } from 'rxjs';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> | Promise<boolean> | boolean {

    // Preserve Auth::checkLogin() logic
    if (this.authService.isAuthenticated()) {
      return true;
    }

    // Navigate to login without return_url query parameter
    // The login component will handle redirect after successful authentication
    this.router.navigate(['/login']);

    return false;
  }
}
