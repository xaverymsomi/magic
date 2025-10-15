import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { ApiService } from './api.service';
import { User, LoginRequest, LoginResponse, RecoverPasswordRequest } from '../../shared/models/auth.models';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();
  private isLoggedInSubject = new BehaviorSubject<boolean>(false);
  public isLoggedIn$ = this.isLoggedInSubject.asObservable();

  constructor(private apiService: ApiService) {
    // Check if user is already logged in (preserve session logic)
    this.checkExistingSession();
  }

  // Preserve Login::login() business logic - use original endpoint
  login(credentials: LoginRequest): Observable<LoginResponse> {
    return this.apiService.post('/login/login', {
      email: credentials.email,
      password: credentials.password,
      captcha: credentials.captcha,
      return_url: credentials.return_url || ''
    }).pipe(
      map((response: any) => {
        // Handle PHP Login response format: { status, code, message, user }
        if (response.status === 'success' && response.code === 200) {
          const userDetails = response.user;
          const user: User = {
            id: userDetails?.id,
            email: userDetails?.email,
            username: userDetails?.username,
            permissions: userDetails?.permissions || []
          };
          
          this.currentUserSubject.next(user);
          this.isLoggedInSubject.next(true);
          
          // Preserve session storage logic (PHP uses sessions)
          if (userDetails?.user_id) {
            sessionStorage.setItem('user_id', userDetails.user_id.toString());
          }
          // Store complete user data for session persistence
          sessionStorage.setItem('user_data', JSON.stringify(user));
          
          return {
            status: true,
            code: response.code,
            message: response.message,
            user: user,
            redirect_url: '/dashboard'
          };
        } else {
          return {
            status: false,
            code: response.code || 100,
            message: response.message || 'Login failed'
          };
        }
      }),
      catchError(error => {
        return of({
          status: false,
          code: 100,
          message: 'Network error occurred'
        });
      })
    );
  }

  // Preserve Login::recover() business logic
  recoverPassword(request: RecoverPasswordRequest): Observable<any> {
    return this.apiService.post('/login/recover', {
      email: request.email
    });
  }

  // Preserve Auth::checkLogin() logic
  logout(): Observable<any> {
    return this.apiService.post('/Logout', {}).pipe(
      map(response => {
        this.currentUserSubject.next(null);
        this.isLoggedInSubject.next(false);
        sessionStorage.clear();
        return response;
      })
    );
  }

  // Preserve permission checking logic from Perm_Auth::verifyPermission()
  hasPermission(permission: string): boolean {
    const user = this.currentUserSubject.value;
    if (!user || !user.permissions) {
      return false;
    }
    return user.permissions.some(p => p.name === permission);
  }

  // Check for existing session (preserve PHP session logic)
  private checkExistingSession(): void {
    // Check if user data exists in sessionStorage
    const userId = sessionStorage.getItem('user_id');
    const userData = sessionStorage.getItem('user_data');
    
    if (userId && userData) {
      try {
        const user = JSON.parse(userData);
        this.currentUserSubject.next(user);
        this.isLoggedInSubject.next(true);
      } catch (error) {
        // Invalid stored data, clear it
        sessionStorage.clear();
      }
    }
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  isAuthenticated(): boolean {
    return this.isLoggedInSubject.value;
  }
}