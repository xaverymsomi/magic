export interface MenuItem {
  id: string;
  name: string;
  icon: string;
  route?: string;
  url?: string;
  link?: string;
  title?: string;
  permission?: string;
  children?: MenuItem[];
  submenus?: MenuItem[]; // Keep for compatibility with old structure
  active?: boolean;
  expanded?: boolean;
  order?: number;
  module?: string;
  action?: string;
  params?: any[];
}

export interface MenuResponse {
  status: boolean;
  code: number;
  message: string;
  data?: MenuItem[];
  menus?: MenuItem[];
}
