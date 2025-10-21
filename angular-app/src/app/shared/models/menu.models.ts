export interface MenuItem {
  id: string;
  name: string;
  icon: string;
  route?: string;
  url?: string;
  permission?: string;
  children?: MenuItem[];
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
