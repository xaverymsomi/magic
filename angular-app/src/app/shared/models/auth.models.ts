export interface User {
  id: string;
  username: string;
  email: string;
  avatar?: string;
  role: string;
  permissions: string[];
  created_at?: string;
  updated_at?: string;
  last_login?: string;
  is_active?: boolean;
}

export interface LoginRequest {
  email: string;
  password: string;
  remember_me?: boolean;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data?: {
    user: User;
    token: string;
    expires_in: number;
  };
  error?: string;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  loading: boolean;
  error: string | null;
}
