import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { ApiService } from '../../../core/services/api.service';
import { LoginRequest } from '../../../shared/models/auth.models';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {
  loginForm: FormGroup;
  recoverForm: FormGroup;
  currentTask: 'login' | 'recover' = 'login';
  captchaImageUrl: string = '';
  processing: boolean = false;
  errorMessage: string = '';
  successMessage: string = '';

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private apiService: ApiService,
    private router: Router,
    private route: ActivatedRoute
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
      captcha: ['', Validators.required]
    });

    this.recoverForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]]
    });
  }

  ngOnInit(): void {
    this.loadCaptcha();

    // Check if already logged in
    if (this.authService.isAuthenticated()) {
      this.router.navigate(['/dashboard']);
    }
  }

  // Preserve captcha loading logic from Login::get_captcha()
  loadCaptcha(): void {
    // Use relative URL - proxy will handle routing to PHP backend
    // Original PHP uses /Login/get_captcha (capital L)
    this.captchaImageUrl = `/Login/get_captcha?${Date.now()}`;
  }

  // Preserve onFocusShowRecaptcha logic (angular-create.js line 63-65)
  onFocusShowRecaptcha(): void {
    // Show captcha tooltip - this preserves the original UX
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
      tooltip.classList.add('show-tooltip');
    }
  }

  // Preserve onKeyShowRecaptcha logic (angular-create.js line 67-72)
  onKeyShowRecaptcha(event: KeyboardEvent): void {
    if (event.which === 13) {
      event.preventDefault();
      const tooltip = document.querySelector('.tooltip');
      if (tooltip) {
        tooltip.classList.add('show-tooltip');
      }
    }
  }

  // Preserve login logic from Login::login()
  onLogin(): void {
    if (this.loginForm.valid && !this.processing) {
      this.processing = true;
      this.errorMessage = '';

      const loginRequest: LoginRequest = {
        email: this.loginForm.value.email,
        password: this.loginForm.value.password,
        captcha: this.loginForm.value.captcha
      };

      this.authService.login(loginRequest).subscribe({
        next: (response) => {
          this.processing = false;

          if (response.success) {
            // Login successful - redirect to dashboard
            this.router.navigate(['/dashboard']);
          } else {
            this.errorMessage = response.message || 'Login failed. Please try again.';
            this.loadCaptcha(); // Reload captcha on failed login
          }
        },
        error: (error) => {
          this.processing = false;
          this.errorMessage = 'An error occurred during login. Please try again.';
          this.loadCaptcha();
        }
      });
    }
  }

  // Preserve password recovery logic from Login::recover()
  onRecover(): void {
    if (this.recoverForm.valid && !this.processing) {
      this.processing = true;
      this.errorMessage = '';
      this.successMessage = '';

      this.authService.recoverPassword({
        email: this.recoverForm.value.email
      }).subscribe({
        next: (response) => {
          this.processing = false;

          if (response.status) {
            this.successMessage = 'Password recovery instructions have been sent to your email.';
            this.recoverForm.reset();
          } else {
            this.errorMessage = response.message || 'Failed to send recovery email.';
          }
        },
        error: (error) => {
          this.processing = false;
          this.errorMessage = 'An error occurred. Please try again later.';
        }
      });
    }
  }

  // Preserve task switching logic (angular-create.js line 315, 342)
  switchTask(task: 'login' | 'recover'): void {
    this.currentTask = task;
    this.errorMessage = '';
    this.successMessage = '';

    if (task === 'login') {
      this.loadCaptcha();
    }
  }

  // Form validation helpers
  isFieldInvalid(form: FormGroup, fieldName: string): boolean {
    const field = form.get(fieldName);
    return !!(field && field.invalid && (field.dirty || field.touched));
  }

  getFieldError(form: FormGroup, fieldName: string): string {
    const field = form.get(fieldName);
    if (field && field.errors) {
      if (field.errors['required']) {
        return `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required`;
      }
      if (field.errors['email']) {
        return 'Please enter a valid email address';
      }
    }
    return '';
  }
}
